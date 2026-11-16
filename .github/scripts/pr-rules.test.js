// Run with: node --test .github/scripts/
const test = require( 'node:test' );
const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const path = require( 'node:path' );
const rules = require( './pr-rules.js' );

const template = fs.readFileSync( path.join( __dirname, '..', 'PULL_REQUEST_TEMPLATE.MD' ), 'utf8' );

const good = `Stops the Logins report CSV export from writing every user once per batch.

### Changelog entry

BUG FIX: Fixed the Logins report CSV export writing every user once per batch. #3945 (@someone)

### Commit message

    Logins CSV export repeated rows because the loop never advanced $i_start

    Refs #3891

### Why

The export loaded every user before the loop and never moved the offset.

Fixes #3891

### How to test

1. Create 5,000 users.
2. Export the Logins report.

### Testing done

Before: 40,000 rows. After: 5,000 rows.

### Risk

None known.

### AI assistance

- [x] This pull request includes AI-generated code or text. Model/tool: Claude Opus 5.5

### Your connection to this change

- [x] I use Paid Memberships Pro on a site and ran into the problem this pull request fixes.
- [ ] I maintain a plugin, gateway or service that integrates with Paid Memberships Pro, and this change concerns that integration.
- [ ] A Paid Memberships Pro team member approved this specific change before I opened it. Link:

### Checklist

- [x] I have read [CONTRIBUTING.md](https://example.com).
- [x] There is a linked issue, and no other open pull request covers it.
`;

const commit = ( message, sha = 'abcdef1234567' ) => ( { sha, message, parents: 1 } );
const outside = ( overrides = {} ) => rules.checkPullRequest( { body: good, base: 'dev', isTeam: false, commits: [ commit( 'Logins CSV export repeated rows because the loop never advanced $i_start\n\nRefs #3891' ) ], ...overrides } );

test( 'a filled-in template passes', () => {
	assert.deepEqual( outside(), [] );
} );

test( 'the unfilled template fails on every required field', () => {
	const errors = rules.checkPullRequest( { body: template, base: 'dev', isTeam: false } );
	const joined = errors.join( '\n' );
	for ( const needle of [ 'one sentence', 'changelog entry must start', 'Commit message', '"Why"', 'How to test', 'Testing done', 'Risk', 'connection to this change', 'CONTRIBUTING.md' ] ) {
		assert.match( joined, new RegExp( needle ), needle );
	}
} );

test( 'a free-form description with no template fails with one clear message', () => {
	const errors = outside( { body: 'The export loops over every user.\n\nFixes #3891' } );
	assert.match( errors[0], /template is missing/ );
	assert.match( errors[0], /gh pr create/ );
} );

test( 'sections out of order fail', () => {
	const swapped = good.replace( '### Testing done', '### TMP' ).replace( '### Risk', '### Testing done' ).replace( '### TMP', '### Risk' );
	assert.match( outside( { body: swapped } ).join( '\n' ), /in order/ );
} );

test( 'changelog entry needs a house label or None:', () => {
	assert.match( outside( { body: good.replace( 'BUG FIX: Fixed', 'Fixed' ) } ).join( '\n' ), /changelog entry must start/ );
	assert.match( outside( { body: good.replace( 'BUG FIX: Fixed', 'Bug fix: Fixed' ) } ).join( '\n' ), /changelog entry must start/ );
	assert.deepEqual( outside( { body: good.replace( /BUG FIX: Fixed.*\n/, 'None: docs only.\n' ) } ), [] );
	assert.deepEqual( outside( { body: good.replace( 'BUG FIX: Fixed', 'BUG FIX/ENHANCEMENT: Fixed' ) } ), [] );
} );

test( 'closing keywords are rejected in commits and the proposed commit message', () => {
	assert.match( outside( { commits: [ commit( 'Loop never advanced\n\nFixes #3891' ) ] } ).join( '\n' ), /Commit abcdef1 uses a closing keyword/ );
	assert.match( outside( { commits: [ commit( 'Loop never advanced; closes strangerstudios/paid-memberships-pro#3891' ) ] } ).join( '\n' ), /closing keyword/ );
	assert.match( outside( { body: good.replace( 'Refs #3891', 'Fixes #3891' ) } ).join( '\n' ), /proposed commit message uses a closing keyword/ );
	assert.deepEqual( outside( { commits: [ commit( 'Refs #3891: loop never advanced' ) ] } ), [] );
} );

test( 'merge commits are ignored', () => {
	assert.deepEqual( outside( { commits: [ { sha: 'abcdef1234567', message: "Merge branch 'dev' into fix", parents: 2 } ] } ), [] );
} );

test( 'what-only commit subjects fail', () => {
	assert.match( outside( { commits: [ commit( 'Update login-csv.php' ) ] } ).join( '\n' ), /only says what changed/ );
	assert.match( outside( { commits: [ commit( 'fix adminpages/login-csv.php' ) ] } ).join( '\n' ), /only says what changed/ );
	assert.deepEqual( outside( { commits: [ commit( 'Update login-csv.php to advance $i_start each batch' ) ] } ), [] );
} );

test( 'Why needs a real issue reference, not the placeholder', () => {
	assert.match( outside( { body: good.replace( 'Fixes #3891', 'Fixes #' ) } ).join( '\n' ), /Link the issue/ );
	assert.deepEqual( outside( { body: good.replace( 'Fixes #3891', 'Fixes https://github.com/strangerstudios/paid-memberships-pro/issues/3891' ) } ), [] );
} );

test( 'outside contributors need an eligibility box; team members do not', () => {
	const unticked = good.replace( '- [x] I use', '- [ ] I use' );
	assert.match( outside( { body: unticked } ).join( '\n' ), /connection to this change/ );
	assert.deepEqual( outside( { body: unticked, isTeam: true } ), [] );
} );

test( 'a ticked team-approval box needs a link', () => {
	const approved = good.replace( '- [x] I use', '- [ ] I use' ).replace( '- [ ] A Paid Memberships Pro team member approved this specific change before I opened it. Link:', '- [x] A Paid Memberships Pro team member approved this specific change before I opened it. Link:' );
	assert.match( outside( { body: approved } ).join( '\n' ), /Link where a team member approved/ );
	assert.deepEqual( outside( { body: approved.replace( 'opened it. Link:', 'opened it. Link: https://github.com/strangerstudios/paid-memberships-pro/issues/3891#issuecomment-1' ) } ), [] );
} );

test( 'a ticked AI box must name the model', () => {
	assert.match( outside( { body: good.replace( 'Model/tool: Claude Opus 5.5', 'Model/tool:' ) } ).join( '\n' ), /Name every model/ );
} );

test( 'base branch: dev and version branches pass, others fail for outside contributors only', () => {
	assert.deepEqual( outside( { base: 'v4.0' } ), [] );
	assert.match( outside( { base: 'master' } ).join( '\n' ), /not `master`/ );
	assert.deepEqual( outside( { base: 'master', isTeam: true } ), [] );
} );

test( 'CRLF bodies from the GitHub web editor parse the same', () => {
	assert.deepEqual( outside( { body: good.replace( /\n/g, '\r\n' ) } ), [] );
} );

test( 'comment wording differs for team and outside contributors', () => {
	assert.match( rules.commentBody( [ 'x' ], false ), /will be closed/ );
	assert.match( rules.commentBody( [ 'x' ], true ), /won't be closed/ );
	assert.ok( rules.commentBody( [], false ).startsWith( rules.MARKER ) );
} );

// A fake GitHub client recording the calls run() and sweep() make.
function fakeGithub( { comments = [], permission = 'read', prs = [], commits = [] } = {} ) {
	const calls = [];
	const record = ( name, result = {} ) => async ( args ) => {
		calls.push( [ name, args ] );
		return { data: result };
	};
	const rest = {
		issues: {
			listComments: 'listComments',
			createComment: record( 'createComment' ),
			updateComment: record( 'updateComment' ),
			addLabels: record( 'addLabels' ),
			removeLabel: record( 'removeLabel' ),
		},
		pulls: { listCommits: 'listCommits', list: 'list', update: record( 'closePull' ) },
		repos: { getCollaboratorPermissionLevel: async () => ( { data: { permission } } ) },
	};
	const pages = { listComments: comments, listCommits: commits, list: prs };
	return { calls, github: { rest, paginate: async ( fn ) => pages[ fn ] } };
}

const core = () => {
	const out = { failed: null, info: () => {} };
	out.setFailed = ( msg ) => ( out.failed = msg );
	return out;
};
const pull = ( overrides = {} ) => ( { number: 7, draft: false, body: good, base: { ref: 'dev' }, user: { login: 'someone', type: 'User' }, author_association: 'NONE', labels: [], updated_at: '2026-11-01T00:00:00Z', ...overrides } );
const context = ( pr ) => ( { repo: { owner: 'strangerstudios', repo: 'paid-memberships-pro' }, payload: { pull_request: pr } } );

test( 'run: a failing outside pull request gets a comment, a label and a failed check', async () => {
	const { github, calls } = fakeGithub( { commits: [ { sha: 'abcdef1234567', commit: { message: 'Update login-csv.php' }, parents: [ {} ] } ] } );
	const c = core();
	await rules.run( { github, context: context( pull() ), core: c } );
	const names = calls.map( ( [ n ] ) => n );
	assert.deepEqual( names, [ 'createComment', 'addLabels' ] );
	assert.match( calls[0][1].body, /will be closed/ );
	assert.ok( c.failed );
} );

test( 'run: a team member is detected by write access and gets the softer comment', async () => {
	const { github, calls } = fakeGithub( { permission: 'write' } );
	await rules.run( { github, context: context( pull( { body: template } ) ), core: core() } );
	assert.match( calls[0][1].body, /won't be closed/ );
} );

test( 'run: fixing the pull request updates the comment to passing and removes the label', async () => {
	const existing = { id: 99, user: { type: 'Bot' }, body: `${ rules.MARKER }\n## Pull request rules: changes needed` };
	const { github, calls } = fakeGithub( { comments: [ existing ], commits: [] } );
	const c = core();
	await rules.run( { github, context: context( pull( { labels: [ { name: rules.LABEL } ] } ) ), core: c } );
	assert.deepEqual( calls.map( ( [ n ] ) => n ), [ 'updateComment', 'removeLabel' ] );
	assert.match( calls[0][1].body, /passing/ );
	assert.equal( c.failed, null );
} );

test( 'run: a passing pull request with no earlier comment gets no comment', async () => {
	const { github, calls } = fakeGithub();
	await rules.run( { github, context: context( pull() ), core: core() } );
	assert.deepEqual( calls, [] );
} );

test( 'run: drafts and bots are skipped', async () => {
	for ( const pr of [ pull( { draft: true, body: '' } ), pull( { body: '', user: { login: 'dependabot[bot]', type: 'Bot' } } ) ] ) {
		const { github, calls } = fakeGithub();
		await rules.run( { github, context: context( pr ), core: core() } );
		assert.deepEqual( calls, [] );
	}
} );

test( 'sweep: closes only outside pull requests past the grace period', async () => {
	const comment = { id: 1, user: { type: 'Bot' }, body: rules.MARKER, updated_at: '2026-11-01T00:00:00Z' };
	const labeled = [ { name: rules.LABEL } ];
	const now = new Date( '2026-11-09T00:00:00Z' );

	let fake = fakeGithub( { comments: [ comment ], prs: [ pull( { labels: labeled } ) ] } );
	await rules.sweep( { github: fake.github, context: context(), core: core(), now } );
	assert.deepEqual( fake.calls.map( ( [ n ] ) => n ), [ 'createComment', 'closePull' ] );

	fake = fakeGithub( { comments: [ comment ], prs: [ pull( { labels: labeled, author_association: 'MEMBER' } ) ] } );
	await rules.sweep( { github: fake.github, context: context(), core: core(), now } );
	assert.deepEqual( fake.calls, [], 'team pull requests are never closed' );

	fake = fakeGithub( { comments: [ comment ], prs: [ pull( { labels: labeled, updated_at: '2026-11-05T00:00:00Z' } ) ] } );
	await rules.sweep( { github: fake.github, context: context(), core: core(), now } );
	assert.deepEqual( fake.calls, [], 'recent activity restarts the grace period' );

	fake = fakeGithub( { comments: [ comment ], prs: [ pull( { labels: [] } ) ] } );
	await rules.sweep( { github: fake.github, context: context(), core: core(), now } );
	assert.deepEqual( fake.calls, [], 'unlabeled pull requests are left alone' );
} );
