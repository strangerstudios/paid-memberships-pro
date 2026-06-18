( function ( tinymce, document, window ) {
	'use strict';

	if ( ! tinymce ) {
		return;
	}

	const CURSOR_MARKER = '__pmpro_cursor__';
	const MENU_CLASS = 'pmpro-liquid-autocomplete';

	tinymce.PluginManager.add(
		'pmpro_liquid_autocomplete',
		function ( editor ) {
			const settings = normalizeSettings(
				editor.getParam( 'pmpro_liquid_autocomplete', null )
			);

			if ( ! settings ) {
				return;
			}

			let menu = null;
			let items = [];
			let activeIndex = -1;
			let activeContext = null;
			let markerIndex = 0;
			const strings = settings.strings || {};

			function handleResize() {
				closeMenu();
			}

			function normalizeSettings( value ) {
				if ( typeof value === 'string' ) {
					try {
						value = JSON.parse( value );
					} catch ( error ) {
						value = {};
					}
				}

				return value && typeof value === 'object' ? value : null;
			}

			function ensureMenu() {
				if ( menu ) {
					return menu;
				}

				menu = document.createElement( 'div' );
				menu.className = MENU_CLASS;
				menu.setAttribute( 'role', 'listbox' );
				menu.setAttribute(
					'aria-label',
					strings.autocompleteLabel || 'Liquid autocomplete'
				);
				menu.hidden = true;
				document.body.appendChild( menu );

				menu.addEventListener( 'mousedown', function ( event ) {
					const itemNode = closest(
						event.target,
						'.pmpro-liquid-autocomplete__item'
					);

					if ( ! itemNode ) {
						return;
					}

					event.preventDefault();
					setActive(
						parseInt( itemNode.getAttribute( 'data-index' ), 10 )
					);
					chooseActive();
				} );

				return menu;
			}

			function ensureAnnouncer( politeness ) {
				const id = 'polite' === politeness
					? 'pmpro-liquid-autocomplete-announcer-polite'
					: 'pmpro-liquid-autocomplete-announcer';
				let announcer = document.getElementById( id );

				if ( ! announcer ) {
					announcer = document.createElement( 'div' );
					announcer.id = id;
					announcer.setAttribute(
						'aria-live',
						'polite' === politeness ? 'polite' : 'assertive'
					);
					announcer.setAttribute( 'aria-atomic', 'true' );
					announcer.className = 'screen-reader-text';
					document.body.appendChild( announcer );
				}

				return announcer;
			}

			function announce( text, politeness ) {
				const announcer = ensureAnnouncer( politeness );
				announcer.textContent = '';
				requestAnimationFrame( function () {
					announcer.textContent = text;
				} );
			}

			function closest( node, selector ) {
				while ( node && node !== document ) {
					if ( node.matches && node.matches( selector ) ) {
						return node;
					}

					node = node.parentNode;
				}

				return null;
			}

			function closeMenu() {
				if ( menu ) {
					menu.hidden = true;
					menu.innerHTML = '';
					menu.removeAttribute( 'aria-activedescendant' );
				}

				[
					'pmpro-liquid-autocomplete-announcer',
					'pmpro-liquid-autocomplete-announcer-polite',
				].forEach( function ( id ) {
					const announcer = document.getElementById( id );
					if ( announcer ) {
						announcer.textContent = '';
					}
				} );

				items = [];
				activeIndex = -1;
				activeContext = null;
			}

			function isMenuOpen() {
				return menu && ! menu.hidden;
			}

			function renderMenu( nextItems, context ) {
				let firstSelectable = -1;
				const container = ensureMenu();

				items = nextItems;
				activeContext = context;
				container.innerHTML = '';

				if ( ! items.length ) {
					closeMenu();
					return;
				}

				items.forEach( function ( item, index ) {
					let itemNode;
					let descriptionNode;

					if ( item.type === 'separator' ) {
						itemNode = document.createElement( 'div' );
						itemNode.className =
							'pmpro-liquid-autocomplete__separator';
						itemNode.textContent = item.label;
						container.appendChild( itemNode );
						return;
					}

					if ( firstSelectable === -1 ) {
						firstSelectable = index;
					}

					itemNode = document.createElement( 'div' );
					itemNode.className = 'pmpro-liquid-autocomplete__item';
					itemNode.setAttribute( 'role', 'option' );
					itemNode.setAttribute( 'data-index', index );
					itemNode.setAttribute( 'id', 'pmpro-liquid-autocomplete-option-' + index );

					const labelNode = document.createElement( 'span' );
					labelNode.className = 'pmpro-liquid-autocomplete__label';
					labelNode.textContent = item.label;
					itemNode.appendChild( labelNode );

					if ( item.description ) {
						descriptionNode = document.createElement( 'span' );
						descriptionNode.className =
							'pmpro-liquid-autocomplete__description';
						descriptionNode.textContent = item.description;
						itemNode.appendChild( descriptionNode );
					}

					container.appendChild( itemNode );
				} );

				const isOpening = container.hidden;
				container.hidden = false;
				positionMenu( context );
				setActive( firstSelectable, isOpening );
			}

			function setActive( index, isOpening ) {
				if ( ! items[ index ] || items[ index ].type === 'separator' ) {
					return;
				}

				activeIndex = index;
				const optionNodes = ensureMenu().querySelectorAll(
					'.pmpro-liquid-autocomplete__item'
				);

				Array.prototype.forEach.call(
					optionNodes,
					function ( optionNode ) {
						const isActive =
							parseInt(
								optionNode.getAttribute( 'data-index' ),
								10
							) === activeIndex;
						optionNode.classList.toggle( 'is-active', isActive );
						optionNode.setAttribute(
							'aria-selected',
							isActive ? 'true' : 'false'
						);

						if ( isActive ) {
							optionNode.scrollIntoView( { block: 'nearest' } );
						}
					}
				);

				ensureMenu().setAttribute(
					'aria-activedescendant',
					'pmpro-liquid-autocomplete-option-' + activeIndex
				);

				const item = items[ activeIndex ];
				const selectableItems = items.filter( function ( i ) {
					return i.type !== 'separator';
				} );
				const position = selectableItems.indexOf( item ) + 1;
				const total = selectableItems.length;
				const itemText =
					item.label +
					( item.description ? ', ' + item.description : '' ) +
					', ' + position + ' of ' + total;
				announce(
					isOpening
						? ( strings.autocompleteOpened ||
						  'Autocomplete list opened' ) +
						  ', ' + itemText
						: itemText,
					isOpening ? 'polite' : 'assertive'
				);
			}

			function moveActive( direction ) {
				let nextIndex = activeIndex;
				let checked = 0;

				if ( ! items.length ) {
					return;
				}

				do {
					nextIndex += direction;

					if ( nextIndex < 0 ) {
						nextIndex = items.length - 1;
					} else if ( nextIndex >= items.length ) {
						nextIndex = 0;
					}

					checked++;
				} while (
					items[ nextIndex ].type === 'separator' &&
					checked <= items.length
				);

				setActive( nextIndex );
			}

			function positionMenu() {
				const caretRect = getCaretRect();
				const iframe = editor.iframeElement;
				const iframeRect = iframe
					? iframe.getBoundingClientRect()
					: { left: 0, top: 0 };
				const viewportWidth =
					window.innerWidth || document.documentElement.clientWidth;
				const viewportHeight =
					window.innerHeight || document.documentElement.clientHeight;
				let left;
				let top;

				if ( ! caretRect ) {
					return;
				}

				left = window.pageXOffset + iframeRect.left + caretRect.left;
				top =
					window.pageYOffset + iframeRect.top + caretRect.bottom + 6;

				menu.style.left = left + 'px';
				menu.style.top = top + 'px';

				const rect = menu.getBoundingClientRect();

				if ( rect.right > viewportWidth - 12 ) {
					left = Math.max(
						12,
						window.pageXOffset + viewportWidth - rect.width - 12
					);
					menu.style.left = left + 'px';
				}

				if ( rect.bottom > viewportHeight - 12 ) {
					top =
						window.pageYOffset +
						iframeRect.top +
						caretRect.top -
						rect.height -
						6;
					menu.style.top =
						Math.max( window.pageYOffset + 12, top ) + 'px';
				}
			}

			function getCaretRect() {
				const range = editor.selection.getRng();
				let rect = null;

				if ( ! range ) {
					return null;
				}

				if ( range.getClientRects ) {
					rect =
						range.getClientRects()[ 0 ] ||
						range.getBoundingClientRect();
				}

				if (
					rect &&
					( rect.left || rect.top || rect.width || rect.height )
				) {
					return rect;
				}

				const clone = range.cloneRange();
				const marker = editor.getDoc().createElement( 'span' );
				marker.appendChild(
					editor.getDoc().createTextNode( '\uFEFF' )
				);
				clone.insertNode( marker );
				rect = marker.getBoundingClientRect();

				if ( marker.parentNode ) {
					marker.parentNode.removeChild( marker );
				}

				editor.selection.setRng( range );

				return rect;
			}

			function getContext() {
				const range = editor.selection.getRng();
				let raw;
				let pipeOffset;

				if (
					! range ||
					! range.collapsed ||
					! range.startContainer ||
					range.startContainer.nodeType !== 3
				) {
					return null;
				}

				const node = range.startContainer;
				const offset = range.startOffset;
				const before = node.data.slice( 0, offset );
				const lastVariableOpen = before.lastIndexOf( '{{' );
				const lastTagOpen = before.lastIndexOf( '{%' );
				const lastTagClose = before.lastIndexOf( '%}' );

				if (
					lastTagOpen > lastTagClose &&
					lastTagOpen >= lastVariableOpen
				) {
					raw = before.slice( lastTagOpen + 2 );

					if ( raw.indexOf( '%}' ) !== -1 ) {
						return null;
					}

					return {
						type: 'tag',
						query: cleanQuery( raw ),
						node,
						start: lastTagOpen,
						end: offset,
					};
				}

				const lastVariableClose = before.lastIndexOf( '}}' );

				if (
					lastVariableOpen > lastVariableClose &&
					lastVariableOpen >= lastTagOpen
				) {
					raw = before.slice( lastVariableOpen + 2 );
					pipeOffset = raw.lastIndexOf( '|' );

					if ( raw.indexOf( '}}' ) !== -1 ) {
						return null;
					}

					if ( pipeOffset !== -1 ) {
						let start = lastVariableOpen + 2 + pipeOffset;

						while (
							start > lastVariableOpen + 2 &&
							/\s/.test( before.charAt( start - 1 ) )
						) {
							start--;
						}

						return {
							type: 'filter',
							query: cleanQuery( raw.slice( pipeOffset + 1 ) ),
							node,
							start,
							end: offset,
						};
					}

					return {
						type: 'variable',
						query: cleanQuery( raw ),
						node,
						start: lastVariableOpen,
						end: offset,
					};
				}

				if (
					before.charAt( before.length - 1 ) === '{' &&
					before.charAt( before.length - 2 ) !== '{'
				) {
					return {
						type: 'initial',
						query: '',
						node,
						start: offset - 1,
						end: offset,
					};
				}

				return null;
			}

			function cleanQuery( value ) {
				return value.replace( /^\s+/, '' ).toLowerCase();
			}

			function getSuggestions( context ) {
				if ( context.type === 'initial' ) {
					return suggestVariables( '' )
						.concat( [
							{
								type: 'separator',
								label:
									strings.liquidTagsHeader || 'Liquid Tags',
							},
						] )
						.concat( suggestTags( '' ) );
				}

				if ( context.type === 'variable' ) {
					return suggestVariables( context.query );
				}

				if ( context.type === 'tag' ) {
					return suggestTags( context.query );
				}

				if ( context.type === 'filter' ) {
					return suggestFilters( context.query );
				}

				return [];
			}

			function suggestVariables( query ) {
				return filterItems( settings.variables || [], query );
			}

			function suggestTags( query ) {
				return filterItems( settings.tags || [], query );
			}

			function suggestFilters( query ) {
				return filterItems( settings.filters || [], query );
			}

			function filterItems( sourceItems, query ) {
				return sourceItems
					.filter( function ( item ) {
						return (
							! query ||
							item.name.toLowerCase().indexOf( query ) !== -1
						);
					} )
					.slice( 0, 100 )
					.map( function ( item ) {
						return {
							type: 'item',
							label: item.label || item.name,
							description: item.description || '',
							insert: item.insert || item.label || item.name,
						};
					} );
			}

			function updateMenu() {
				const context = getContext();

				if ( ! context ) {
					closeMenu();
					return;
				}

				const nextItems = getSuggestions( context );
				renderMenu( nextItems, context );
			}

			function chooseActive() {
				const item = items[ activeIndex ];

				if ( ! item || item.type === 'separator' || ! activeContext ) {
					return;
				}

				editor.focus();
				editor.undoManager.transact( function () {
					replaceContextWith( activeContext, item.insert );
				} );
				closeMenu();
			}

			function replaceContextWith( context, insert ) {
				const range = editor.getDoc().createRange();
				const markerId =
					'pmpro-liquid-autocomplete-cursor-' + ++markerIndex;
				const markerHtml =
					'<span id="' +
					markerId +
					'" data-mce-type="bookmark"></span>';
				let markerNode;

				range.setStart( context.node, context.start );
				range.setEnd( context.node, context.end );
				editor.selection.setRng( range );

				if ( insert.indexOf( CURSOR_MARKER ) !== -1 ) {
					editor.insertContent(
						insert.replace( CURSOR_MARKER, markerHtml )
					);
					markerNode = editor.dom.get( markerId );

					if ( markerNode ) {
						editor.selection.select( markerNode );
						editor.selection.collapse( true );
						editor.dom.remove( markerNode );
					}
				} else {
					editor.insertContent( insert );
				}
			}

			function shouldIgnoreKeyup( event ) {
				return (
					event.keyCode === 9  ||  // Tab
					event.keyCode === 13 ||  // Enter
					event.keyCode === 16 ||  // Shift
					event.keyCode === 17 ||  // Ctrl
					event.keyCode === 18 ||  // Alt
					event.keyCode === 20 ||  // Caps Lock
					event.keyCode === 27 ||  // Escape
					event.keyCode === 38 ||  // Up arrow
					event.keyCode === 40 ||  // Down arrow
					event.keyCode === 91 ||  // Meta left
					event.keyCode === 92 ||  // Meta right
					event.keyCode === 93     // Context menu / Meta
				);
			}

			editor.on( 'keyup', function ( event ) {
				if ( shouldIgnoreKeyup( event ) ) {
					return;
				}

				updateMenu();
			} );

			editor.on(
				'keydown',
				function ( event ) {
					if ( ! isMenuOpen() ) {
						return;
					}

					if ( event.keyCode === 27 ) {
						event.preventDefault();
						event.stopPropagation();
						closeMenu();
						announce(
							strings.autocompleteClosed ||
							'Autocomplete list closed'
						);
					} else if ( event.keyCode === 38 ) {
						event.preventDefault();
						event.stopPropagation();
						moveActive( -1 );
					} else if ( event.keyCode === 40 ) {
						event.preventDefault();
						event.stopPropagation();
						moveActive( 1 );
					} else if ( event.keyCode === 13 || event.keyCode === 9 ) {
						event.preventDefault();
						event.stopPropagation();
						chooseActive();
					}
				},
				true
			);

			editor.on( 'click blur hide', closeMenu );
			editor.on( 'ScrollContent ResizeEditor', function () {
				if ( isMenuOpen() && activeContext ) {
					positionMenu( activeContext );
				}
			} );
			editor.on( 'remove', function () {
				closeMenu();
				window.removeEventListener( 'resize', handleResize );

				if ( menu && menu.parentNode ) {
					menu.parentNode.removeChild( menu );
				}
			} );

			window.addEventListener( 'resize', handleResize );
		}
	);
} )( window.tinymce, document, window );
