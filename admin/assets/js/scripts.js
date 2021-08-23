(function( $, document, window ){
	'use strict'

	var Scoda = {

		/**
		 * All Selectors and class names  
		 */
		elem: {
			_document: $( document ),
			_window: $( window ),
			hideAll: $('.scoda-hide-all'),
			tabs: $( '.fired-tabs' ).children( 'li' ), 
			tabContent: $( '.scoda-panel-contents' ),
			preloader: $( '.scoda-preloader' ),
			sortable: $( '#scoda-sortable-ui' ),
			selector: $( '.scoda-network-switcher' ),
			classes: {
				tabActivator: '.scoda-activated-tab'
			}
		},

		/**
		 * Initialize all methods and events 
		 */
		init: function() {
			 
			// Events Methods  
			this.loadWindow();
			this.openPanelTab();

			// Compatible the tab content height
			this.compatibleHeightForTabContent();

			// Calling another liberary 
			this.startSortable(); // jQuery UI Sortable 
			this.comboBox(); // Select 2
		},

		/**
		 * To init select2 jquery lib
		 */
		comboBox: function() {
			
			var that 			 = this;
			var sn_slug 		 = that.elem._document[0].URL.split('-').pop(); 
			var elem 			 = $( that.elem.selector );
			var selectBox;
			var currentFormState = function( state ) {
				
				if ( ! state.text ) {
					return;
				}

				if ( ! state.id || ! state.icon || ! state.href ) {
					return state.text;
				}

				var buildRow = $('<span data-id=\'' + state.id + '\'><i class=\'scoda-sc-icon ' + state.icon + '\'></i><span class=\'scoda-sc-text\'></span>' + state.text + '</span>');

				return buildRow;

			};

			if ( ! elem.length ) {
				return;
			}
			
			// prepare data of select 2
			var data = that.arrayFilter( scoda_obj.networks,
				[ 
					'slug', 
					'title', 
					'icon', 
					'tab_slug'
				], 
				[
					'id', 
					'text', 
					'icon', 
					'href'
				]
			);
			
			// Init select2 jquery lib 
			selectBox = elem.select2({
				data: data,
				width: '300px', 
				height:'40px',
				templateResult: currentFormState,
  				templateSelection: currentFormState
			});

			// set default value when page is loaded
			that.changeSelectBoxValue( sn_slug );

			// Fire this once select the option 
			selectBox.on( 'select2:select', function( e ) {
				
				// Change Url 
				window.location.href = '#scoda-networks-' + this.value;

				// Select tab panel 
				var index = that.finder( data, 'id', this.value );
				var tab   = '#' + data[index].href;
				
				

				// Open Panel Tab
				that.loadPanelContents( tab );

			});

			
		},

		/**
		 * Change Value of select box 
		 */
		changeSelectBoxValue: function( slug ) {
			
			$( this.elem.selector ).val( slug );
			$( this.elem.selector ).trigger( 'change' );

		},

		/**
		 * To Extract values with new keys  
		 */
		arrayFilter: function( arrayData, neededValue, newKeys ) {

			var render = new Array();
			
			if ( ! arrayData.length ) {
				return;
			}

			if ( neededValue.length !== newKeys.length ) {
				return;
			}

			$.each( arrayData, function( index, item ){
				
				var objx   = new Object();

				for ( var i = 0; i < neededValue.length; i++) {					
					
					var oldKey = neededValue[i];
					
					if ( item[oldKey] ) {
						objx[newKeys[i]] = item[oldKey];
					}

				}
				
				render.push( objx );
			});

			return render;
		},

		/**
		 * To Sort UI Lists  
		 */
		startSortable: function() {
			 
			if ( !this.elem.sortable.length ) {
				return;
			}

			var that = this;

			// To Start Sorting Elements 
			this.elem.sortable.sortable({
				
				stop: function( event, ui ) {
					
					// Needed Givens 
					var networks = new Array();
					var ui 		 = $( event.target );
					
					// to prevent any error
					if ( ! ui.length ) {
						return;
					}

					// extract networks names from list 
					ui.children( 'li' ).each(function( index, item ){
						
						var elem = $( this );
						if ( elem.attr( 'data-network' ) ) {
							networks.push( $.trim( elem.attr( 'data-network' ) ) );
						}

					});
					
					// Send the new sorted list to the server
					if ( networks.length && scoda_obj ) {

						$.ajax({
							
							url: scoda_obj.ajax_url,
							method: "POST",
							data: {
								'action': 	'sorting_scoda_social_networks',
								'secure': 	scoda_obj.nonce ,
								'networks': networks
							},
							success: function( result ) {
								console.log( result );
							},
							error: function( msg ) {
								
							}

						});

					}

				}

			});

		},

		/**
		 * Once window is loaded fire the following methods
		 */
		loadWindow: function() {
			
			var that = this;

			// Fire All Methods once page is loaded 
			this.elem._window.on( 'load', function(){
				
				// hide preloader 
				that.stopPreloader();

				// To Load All Content Of Any Tab 
				that.getCurrentTab();

			});

		}, 

		/**
		 * Hide and stop preloader 
		 */
		stopPreloader: function() {
			
			var that = this;

			if ( ! that.elem.preloader.length ) {
				return;
			}

			setTimeout(function() {
				that.elem.preloader.fadeOut();
			}, 1000 );

		},

		/**
		 * Full height with the tab content 
		 */
		compatibleHeightForTabContent: function() {

			if ( ! this.elem.tabContent.length ) {
				return;
			}

			var newTabContentHeight = this.elem.tabContent.parent().height() + 'px';

			this.elem.tabContent.css( "min-height", newTabContentHeight );

		},

		/**
		 * Load Tab Contents according to url 
		 */
		getCurrentTab: function() {
			
			// current document url
			var url = this.elem._document[0].URL; 

			// Default tab 
			var tab = 'scoda-dashboard';

			// Get current tab name  
			if ( url.indexOf( '#' ) !== -1 ) {
				tab = url.substring( url.indexOf( '#' ) + 1 );
			}

			// set hash of tab
			tab = '#' + tab; 

			// Case current tab doesn't exists in our wrappers 
			if ( ! $( tab + '-content'  ).length ) {
				tab = '#scoda-dashboard';
			}
			
			// Load Panel Contents 
			this.loadPanelContents( tab );

		},

		/**
		 * Load and get panel contents 
		 */
		loadPanelContents: function( tab ) {

			// Hide All needed blocks
			this.elem.hideAll.hide();

			// get content container 
			var contentContainer = $( tab  + '-content' );
			if ( ! contentContainer.length ) {
				return;
			}

			// get the tab activator name
			var activator = this.elem.classes.tabActivator.replace( '.', '' );
			
			// First Disable Old Activated Tab  
			var activatedClass = $( this.elem.classes.tabActivator );
		
			if ( activatedClass.length ) {
				
				// Get the previous panel tab 
				var prevPanelTab  = activatedClass.attr( 'href' );
				 
				if ( ! prevPanelTab ) {
					return;
				}

				var prevPanelCont = $( activatedClass.attr( 'href' ) + '-content');

				if ( prevPanelCont.length ) {

					// Disable Panel Contents 
					prevPanelCont.hide();

					// Disable Panel Tab
					activatedClass.removeClass( activator );
					
				}

			}

			// Lets activate the new tab 
			contentContainer.show();

			// Enable Network Switcher 
			if ( scoda_obj.networks.length ) {
				// alert( tab.replace( '#', '' ) );
				var index = this.finder( scoda_obj.networks, 'tab_slug', tab.replace( '#', '' ) );
				
				if ( index !== -1 ) {
					
					// Store the new tab 
					var sn_slug = tab.split('-').pop(); 
					this.changeSelectBoxValue( sn_slug );

					// show the current panel 
					$( '.scoda-network-switcher-block' ).show();
					$( 'a[href=\'#scoda-networking\']' ).addClass( activator );
					
					return;

				} else {

					$( '.scoda-network-switcher-block' ).hide();

				}

			}

			// mark activated tab 
			$( 'a[href=\'' + tab + '\']' ).addClass( activator );
			 
		},

		/**
		 * To extract object from array 
		 * no map no filter no findIndex to browser backward 
		 */
		finder: function( items, name, value ) {

			var index = -1;
			
			for (var i = 0; i < items.length; ++i) {
				
				if ( items[i][name] == value ) {

					index = i;
					
					break;
				}

			}

			return index;

		},

		/**
		 * Load Panel Tab
		 */
		openPanelTab: function() {
			
			if ( ! this.elem.tabs.length ) {
				return;
			}
			
			var that = this; 

			// List Of Panel Tabs
			this.elem.tabs.each(function( index, item ) {

				var anchor = $( this ).find( 'a' );
				anchor.on( 'click', function( e ){
					 
					// Get Tab Slug
					var tab = $( this ).attr( 'href' );

					// Load Panel Contents
					that.loadPanelContents( tab );					

				});

			});

		}

	};
	
	Scoda.init();

})( jQuery, document, window );