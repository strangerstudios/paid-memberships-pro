/**
 * Pull request rules check for Paid Memberships Pro.
 *
 * Runs from .github/workflows/pr-rules.yml through actions/github-script.
 * The rules themselves are documented in .github/CONTRIBUTING.md.
 */

const MARKER = '<!-- pmpro-pr-rules -->';
const LABEL = 'needs: pr rules';
const GRACE_DAYS = 7;
const CONTRIBUTING_URL = 'https://github.com/strangerstudios/paid-memberships-pro/blob/dev/.github/CONTRIBUTING.md';
const TEMPLATE_URL = 'https://github.com/strangerstudios/paid-memberships-pro/blob/dev/.github/PULL_REQUEST_TEMPLATE.MD';

// Required template sections, in order.
const SECTIONS = [
	'Changelog entry',
	'Commit message',
	'Why',
	'How to test',
	'Testing done',
	'Risk',
	'AI assistance',
	'Your connection to this change',
	'Checklist',
];

const CHANGELOG_LABELS = /^(?:[*-]\s+)?(SECURITY|FEATURE|ENHANCEMENT|BUG FIX\/ENHANCEMENT|BUG FIX|REFACTOR|DEPRECATED): \S/;
const CHANGELOG_NONE = /^(?:[*-]\s+)?None: \S/i;
const CLOSING_KEYWORD = /\b(close[sd]?|fix(e[sd])?|resolve[sd]?)\b:?\s+([\w.-]+\/[\w.-]+)?#\d+/i;
const ISSUE_REFERENCE = /(#\d+|https:\/\/github\.com\/[\w.-]+\/[\w.-]+\/issues\/\d+)/;
const WHAT_ONLY_SUBJECT = /^(update[sd]?|change[sd]?|edit(ed|s)?|modif(y|ied|ies)|fix(e[sd])?|tweak(ed|s)?)\s+[\w./-]+\.(php|js|jsx|css|scss|txt|md|json|html)\.?$/i;
const BASE_BRANCH = /^(dev|v\d+\.\d+(\.\d+)?)$/;
const TEAM_ASSOCIATIONS = [ 'OWNER', 'MEMBER', 'COLLABORATOR' ];
const TEAM_PERMISSIONS = [ 'admin', 'maintain', 'write' ];

/**
 * Remove HTML comments and trim.
 *
 * @param {string} text Raw Markdown.
 * @return {string}
 */
function stripComments( text ) {
	return ( text || '' ).replace( /<!--[\s\S]*?-->/g, '' ).trim();
}

/**
 * Split a pull request body into its summary and ### sections.
 *
 * @param {string} body Pull request body.
 * @return {{summary: string, order: string[], sections: Object<string, string>}}
 */
function parseBody( body ) {
	const lines = ( body || '' ).replace( /\r\n/g, '\n' ).split( '\n' );
	const sections = {};
	const order = [];
	let current = null;
	const summary = [];
	for ( const line of lines ) {
		const heading = line.match( /^###\s+(.+?)\s*$/ );
		if ( heading ) {
			current = heading[1];
			order.push( current );
			sections[ current ] = '';
			continue;
		}
		if ( current === null ) {
			summary.push( line );
		} else {
			sections[ current ] += line + '\n';
		}
	}
	return { summary: stripComments( summary.join( '\n' ) ), order, sections };
}

/**
 * Lines that are ticked checkboxes.
 *
 * @param {string} text Section text.
 * @return {string[]}
 */
function tickedBoxes( text ) {
	return stripComments( text ).split( '\n' ).filter( ( line ) => /^\s*[-*]\s+\[[xX]\]/.test( line ) );
}

/**
 * Check a pull request against the rules.
 *
 * @param {Object}   pr
 * @param {string}   pr.body    Pull request body.
 * @param {string}   pr.base    Base branch name.
 * @param {boolean}  pr.isTeam  Whether the author is on the Paid Memberships Pro team.
 * @param {Object[]} pr.commits Commits as { sha, message, parents }.
 * @return {string[]} Problems found, empty when the pull request passes.
 */
function checkPullRequest( { body, base, isTeam, commits = [] } ) {
	const errors = [];
	const { summary, order, sections } = parseBody( body );

	const missing = SECTIONS.filter( ( name ) => ! order.includes( name ) );
	if ( missing.length ) {
		errors.push( `The pull request template is missing ${ missing.map( ( m ) => `"${ m }"` ).join( ', ' ) }. Copy the [template](${ TEMPLATE_URL }) into the description and fill in every section. \`gh pr create\` and the API do not load it for you.` );
	} else {
		const found = order.filter( ( name ) => SECTIONS.includes( name ) );
		if ( found.join( '|' ) !== SECTIONS.join( '|' ) ) {
			errors.push( `Keep the template sections in order: ${ SECTIONS.join( ', ' ) }.` );
		}
	}

	if ( ! summary ) {
		errors.push( 'Start the description with one sentence on what the pull request does.' );
	}

	const section = ( name ) => stripComments( sections[ name ] );

	if ( order.includes( 'Changelog entry' ) ) {
		const entry = section( 'Changelog entry' ).split( '\n' )[0].trim();
		if ( ! CHANGELOG_LABELS.test( entry ) && ! CHANGELOG_NONE.test( entry ) ) {
			errors.push( 'The changelog entry must start with one label (SECURITY, FEATURE, ENHANCEMENT, BUG FIX/ENHANCEMENT, BUG FIX, REFACTOR or DEPRECATED) followed by a colon, or with `None:` and the reason when nothing is user-facing.' );
		}
	}

	if ( order.includes( 'Commit message' ) ) {
		const message = section( 'Commit message' );
		if ( ! message ) {
			errors.push( 'Fill in "Commit message" with the message to merge with: why the change had to happen, in one line.' );
		} else if ( CLOSING_KEYWORD.test( message ) ) {
			errors.push( 'The proposed commit message uses a closing keyword (`Fixes`, `Closes` or `Resolves`). Use `Refs #123` in commits and keep `Fixes #123` in "Why".' );
		}
	}

	if ( order.includes( 'Why' ) ) {
		const why = section( 'Why' ).replace( /^\s*(fixes|closes|resolves)\s+#\s*$/gim, '' ).trim();
		if ( ! why ) {
			errors.push( 'Fill in "Why" with the bug, constraint or decision behind the change.' );
		} else if ( ! ISSUE_REFERENCE.test( why ) ) {
			errors.push( 'Link the issue this pull request addresses in "Why", for example `Fixes #123`. Every pull request needs an issue.' );
		}
	}

	if ( order.includes( 'How to test' ) && ! section( 'How to test' ).replace( /^\s*\d+\.\s*$/gm, '' ).trim() ) {
		errors.push( 'Fill in "How to test" with steps a reviewer can follow.' );
	}
	for ( const name of [ 'Testing done', 'Risk' ] ) {
		if ( order.includes( name ) && ! section( name ) ) {
			errors.push( `Fill in "${ name }".` );
		}
	}

	if ( order.includes( 'AI assistance' ) ) {
		for ( const line of tickedBoxes( sections[ 'AI assistance' ] ) ) {
			const tool = line.split( /Model\/tool:/i )[1];
			if ( tool !== undefined && ! tool.trim() ) {
				errors.push( 'Name every model or tool used after "Model/tool:" in "AI assistance".' );
			}
		}
	}

	if ( ! isTeam && order.includes( 'Your connection to this change' ) ) {
		const ticked = tickedBoxes( sections[ 'Your connection to this change' ] );
		if ( ! ticked.length ) {
			errors.push( 'Tick at least one box in "Your connection to this change". We accept pull requests from people who use Paid Memberships Pro and hit the problem, maintain an integration it concerns, or had the change approved by the team first.' );
		}
		const approved = ticked.find( ( line ) => /approved/i.test( line ) );
		if ( approved && ! /https?:\/\/\S+|#\d+/.test( approved ) ) {
			errors.push( 'Link where a team member approved this change, next to that box.' );
		}
	}

	if ( order.includes( 'Checklist' ) && ! tickedBoxes( sections.Checklist ).some( ( line ) => /CONTRIBUTING/i.test( line ) ) ) {
		errors.push( 'Tick the "I have read CONTRIBUTING.md" box after reading it.' );
	}

	for ( const commit of commits ) {
		if ( commit.parents > 1 ) {
			continue;
		}
		const short = commit.sha.slice( 0, 7 );
		const subject = commit.message.split( '\n' )[0].trim();
		if ( CLOSING_KEYWORD.test( commit.message ) ) {
			errors.push( `Commit ${ short } uses a closing keyword. Use \`Refs #123\` in commit messages; a closing keyword closes the issue as soon as the commit reaches \`dev\`.` );
		}
		if ( WHAT_ONLY_SUBJECT.test( subject ) ) {
			errors.push( `Commit ${ short } ("${ subject }") only says what changed. Say why the change had to happen.` );
		}
	}

	if ( ! isTeam && ! BASE_BRANCH.test( base ) ) {
		errors.push( `Open the pull request against \`dev\` for bug fixes, or the milestoned version branch (such as \`v4.0\`) for features, not \`${ base }\`.` );
	}

	return errors;
}

/**
 * Text of the bot comment.
 *
 * @param {string[]} errors Problems found.
 * @param {boolean}  isTeam Whether the author is on the team.
 * @return {string}
 */
function commentBody( errors, isTeam ) {
	if ( ! errors.length ) {
		return `${ MARKER }\n## Pull request rules: passing\n\nThis pull request now follows the [contributing rules](${ CONTRIBUTING_URL }). Thanks.`;
	}
	const consequence = isTeam
		? 'You\'re on the Paid Memberships Pro team, so this pull request won\'t be closed, but please fix these before asking for review.'
		: `If these aren't fixed within ${ GRACE_DAYS } days, this pull request will be closed. Edit the description or push a commit and the check runs again.`;
	return [
		MARKER,
		'## Pull request rules: changes needed',
		'',
		`This pull request doesn't follow the [contributing rules](${ CONTRIBUTING_URL }) yet:`,
		'',
		...errors.map( ( e ) => `- ${ e }` ),
		'',
		consequence,
	].join( '\n' );
}

/**
 * Whether a GitHub user is on the Paid Memberships Pro team.
 *
 * @return {Promise<boolean>}
 */
async function isTeamMember( github, owner, repo, user, association ) {
	if ( user.type === 'Bot' || TEAM_ASSOCIATIONS.includes( association ) ) {
		return true;
	}
	try {
		const { data } = await github.rest.repos.getCollaboratorPermissionLevel( { owner, repo, username: user.login } );
		return TEAM_PERMISSIONS.includes( data.permission );
	} catch ( error ) {
		return false;
	}
}

/**
 * Find the rules comment on a pull request.
 *
 * @return {Promise<Object|undefined>}
 */
async function findComment( github, owner, repo, number ) {
	const comments = await github.paginate( github.rest.issues.listComments, { owner, repo, issue_number: number, per_page: 100 } );
	return comments.find( ( c ) => c.user.type === 'Bot' && c.body.includes( MARKER ) );
}

/**
 * Check the pull request in the current event and comment on the result.
 */
async function run( { github, context, core } ) {
	const pr = context.payload.pull_request;
	const { owner, repo } = context.repo;
	if ( pr.draft ) {
		core.info( 'Draft pull request; the rules are checked when it is marked ready for review.' );
		return;
	}
	if ( pr.user.type === 'Bot' ) {
		core.info( `Skipping bot author ${ pr.user.login }.` );
		return;
	}

	const isTeam = await isTeamMember( github, owner, repo, pr.user, pr.author_association );
	const commits = ( await github.paginate( github.rest.pulls.listCommits, { owner, repo, pull_number: pr.number, per_page: 100 } ) )
		.map( ( c ) => ( { sha: c.sha, message: c.commit.message, parents: c.parents.length } ) );
	const errors = checkPullRequest( { body: pr.body, base: pr.base.ref, isTeam, commits } );
	const existing = await findComment( github, owner, repo, pr.number );
	const labels = pr.labels.map( ( l ) => l.name );

	if ( errors.length ) {
		const body = commentBody( errors, isTeam );
		if ( existing ) {
			if ( existing.body !== body ) {
				await github.rest.issues.updateComment( { owner, repo, comment_id: existing.id, body } );
			}
		} else {
			await github.rest.issues.createComment( { owner, repo, issue_number: pr.number, body } );
		}
		if ( ! labels.includes( LABEL ) ) {
			await github.rest.issues.addLabels( { owner, repo, issue_number: pr.number, labels: [ LABEL ] } );
		}
		core.setFailed( `Pull request rules: ${ errors.length } problem(s). See the comment on the pull request.` );
		return;
	}

	if ( existing && ! existing.body.includes( 'rules: passing' ) ) {
		await github.rest.issues.updateComment( { owner, repo, comment_id: existing.id, body: commentBody( [], isTeam ) } );
	}
	if ( labels.includes( LABEL ) ) {
		await github.rest.issues.removeLabel( { owner, repo, issue_number: pr.number, name: LABEL } );
	}
	core.info( 'Pull request rules: passing.' );
}

/**
 * Close pull requests from outside the team that still fail the rules after the grace period.
 *
 * @param {Object} args
 * @param {Date}   [args.now] Current time, for tests.
 */
async function sweep( { github, context, core, now = new Date() } ) {
	const { owner, repo } = context.repo;
	const prs = await github.paginate( github.rest.pulls.list, { owner, repo, state: 'open', per_page: 100 } );
	for ( const pr of prs ) {
		if ( pr.draft || ! pr.labels.some( ( l ) => l.name === LABEL ) ) {
			continue;
		}
		if ( await isTeamMember( github, owner, repo, pr.user, pr.author_association ) ) {
			continue;
		}
		const comment = await findComment( github, owner, repo, pr.number );
		if ( ! comment ) {
			continue;
		}
		const since = Math.max( Date.parse( comment.updated_at ), Date.parse( pr.updated_at ) );
		if ( now - since < GRACE_DAYS * 24 * 60 * 60 * 1000 ) {
			continue;
		}
		await github.rest.issues.createComment( {
			owner,
			repo,
			issue_number: pr.number,
			body: `Closing because this pull request still doesn't follow the [contributing rules](${ CONTRIBUTING_URL }) after ${ GRACE_DAYS } days. Fix the items in the comment above and reopen it, or open a new pull request.`,
		} );
		await github.rest.pulls.update( { owner, repo, pull_number: pr.number, state: 'closed' } );
		core.info( `#${ pr.number }: closed` );
	}
}

module.exports = { run, sweep, checkPullRequest, commentBody, parseBody, MARKER, LABEL, GRACE_DAYS };
