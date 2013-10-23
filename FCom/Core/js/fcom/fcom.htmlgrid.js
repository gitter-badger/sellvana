define(['backbone', 'underscore', 'jquery', 'jquery.cookie', 'jquery.tablesorter','jquery.dragtable'], function(Backbone, _, $) {
    
    var BackboneGrid = {
        Models: {},
        Collections: {},
        Views: {}
    }
    

    BackboneGrid.Models.Row = Backbone.Model.extend({ 
        defaults: {
            _actions: ''
        }       
    });

    BackboneGrid.Collections.Rows = Backbone.Collection.extend({
        model: BackboneGrid.Models.Row
    });

    BackboneGrid.Views.RowView = Backbone.View.extend({       
        //template: _.template($('#row-template').html()),
        render: function() {
            this.setElement($(this.template(this.model.toJSON())));            
            return this;
        }
    });

    BackboneGrid.Views.GridView = Backbone.View.extend({
        //el: '.fcom-htmlgrid__grid tbody',
        render: function() {
            this.collection.each(this.addRow, this);
            return this;
        },
        addRow: function(row) {
            var rowView = new BackboneGrid.Views.RowView({
                model: row
            });
            this.$el.append(rowView.render().el);
        }
    });

    FCom.BackboneGrid = function(config) {
        BackboneGrid.Views.GridView.prototype.el = "#"+config.id+" tbody";
        BackboneGrid.Views.RowView.prototype.template = _.template($('#'+config.templateId).html());
        console.log(config.data_url);
        var rows = config.data.data;
        var rowsCollection = new BackboneGrid.Collections.Rows;

        for(var i in rows) {
                
            if (i%2 === 0) {
                rows[i].cssClass = "even";
            } else {
                rows[i].cssClass = "odd";
            }
            var rowModel = new BackboneGrid.Models.Row(rows[i]);
            rowsCollection.add(rowModel);
        }

        var gridView = new BackboneGrid.Views.GridView({collection: rowsCollection});
        gridView.render();
    }
    FCom.HtmlGrid = function(config) {
                
        var gridEl = $('#'+config.id);
        var gridParent = gridEl.parent();
        var gridSelection = config.selection || {};

        // update URL and load grid partial
        function load(url) {
            if (url) {
                config.grid_url = url;
            }
            gridParent.load(config.grid_url, function(response, status, xhr) {
                initDOM();
            })
        }

        // set multiselect selections
        function setSelection(selection)
        {
            var sel = selection || gridSelection;
            for (var i in sel) {
                $('tbody tr[data-id='+i+'] .js-sel', gridParent).prop('checked', sel[i]);
                gridSelection[i] = sel[i];
            }
            $('#'+config.id+'-selection').val( Object.keys(gridSelection).join('|') );
        }

        // initialize DOM after loading partial
        function initDOM() {
            // initialize unsaved selections
            setSelection();

            var $table = $('table.fcom-htmlgrid__grid', gridParent);
            
            $('.showhide_column').each(function(){
                $(this).attr('checked','checked');
            })
            $('.showhide_column').bind('click',function(e){

                var id = $(this).data('id');
                
                    $('.table-bordered  tr').each(function() {
                        $(this).children('th').each(function()
                        {
                            if ($(this).data('id') == id)
                            {
                                index = $(this).index();
                            }
                        });

                    
                    $('td:eq(' + index + ')',this).toggle();
                    $('th:eq(' + index + ')',this).toggle();
                });
                //$('.dropdown-toggle').dropdown('toggle');
                e.stopPropagation();
            })
            
            
             
                $( ".dropdown_menu" ).sortable({
                    revert: true,
                });
//                $( ".dropdown-menu li" ).draggable({
//                    connectToSortable: ".dropdown_menu",
//                    helper: "clone",
//                    revert: "invalid"
//                });
                $( "ul, li" ).disableSelection();
            
            
            
             $( ".dropdown-menu" ).sortable({
                revert: true
            });
	
  	  $('.dropdown-menu').droppable({
	    drop: function(event, ui) {
                
                var cols = [];
                $('.dropdown-menu').find("input").each(function(i, el) {
                            cols.push({ name: $(el).data('id') });
                });
                
                $.ajaxSetup({ async: false });

                    $.post(config.personalize_url,
                        { 'do': 'grid.col.order', grid: config.id, cols: JSON.stringify(cols) },
                        function(response, status, xhr) {
                            //console.log(response, status, xhr);
                              if (response.success) {
                               load();
                            }
                        }
                    )

	    }
	  });
	
            
            // resize columns
            
            $('thead th', gridParent).resizable({
                handles: 'e',
                minWidth: 20,
                stop: function(ev, ui) {
                    var $el = ui.element, width = $el.width();
                    //$('tbody td[data-col="'+$el.data('id')+'"]', gridParent).width(width);
                    $.post(config.personalize_url,
                        { 'do': 'grid.col.width', grid: config.id, col: $el.data('id'), width: width },
                        function(response, status, xhr) {
                            //console.log(response, status, xhr);
                        }
                    );
                }
            });
            /*
            $table.colResizable({
                liveDrag: true,
                draggingClass: 'dragging',
                onResize: function(a, b, c) {
    console.log(a, b, c, this); return;
                    var $el = ui.element;
                    $.post(config.personalize_url,
                        { 'do': 'grid.col.width', grid: config.id, col: $el.data('id'), width: $el.width() },
                        function(response, status, xhr) {
                            //console.log(response, status, xhr);
                        }
                    )
                }
            });
            */

            // reorder columns
            
//            $table.dragtable({
//                handle: 'drag-handle',
//                items: 'thead .drag-handle',
//                scroll: true,
//                appendParent: $table,
//                change: function() {
//                    //console.log($('.dragtable-drag-wrapper').html());
//                },
//                stop: function() {
//                    var cols = [];
//                    $('thead th', gridParent).each(function(i, el) {
//                        cols.push({ name: $(el).data('id') });
//                    });
//                    $.post(config.personalize_url,
//                        { 'do': 'grid.col.order', grid: config.id, cols: JSON.stringify(cols) },
//                        function(response, status, xhr) {
//                            //console.log(response, status, xhr);
//                        }
//                    );
//                }
//            });

            
//            $('thead', gridParent).sortable({
//                items: 'th',
//                containment:'parent',
//                update: function(ev, ui) {
//                    var cols = [];
//                    $('th', this).each(function(i, el) {
//                        cols.push({ name: $(el).data('id') });
//                    });
//                    $.post(config.personalize_url,
//                        { 'do': 'grid.col.order', grid: config.id, cols: JSON.stringify(cols) },
//                        function(response, status, xhr) {
//                            console.log(response, status, xhr);
//                            if (response.success) {
//                                load();
//                            }
//                        }
//                    );
//                }
//            });
            
        }
        // initialize DOM first time on page load
        initDOM();

            // handle toolbar and pager selects and inputs
        gridParent.on('change', 'select.js-change-url, input.js-change-url', function(ev) {
            load( $(this).data('href').replace('-VALUE-', this.value) );
        });

        // handle sort labels
        gridParent.on('click', 'a.js-change-url', function(ev) {
            if ($(this).parent().hasClass('disabled')) {
                return false;
            }
            load( this.href );
            return false;
        });

        // handle grid top multiselect toggle
        gridParent.on('change', 'thead select.js-sel', function(ev) {
            var action = this.value, $cb;
            switch (action) {
                case 'show_all':
                    load(setUrlParam(config.grid_url, { selected:false }));
                    break;

                case 'show_sel':
                    load(setUrlParam(config.grid_url, { selected:'sel' }));
                    break;

                case 'show_unsel':
                    load(setUrlParam(config.grid_url, { selected:'unsel' }));
                    break;

                case 'upd_sel': case 'upd_unsel':
                    var sel = {};
                    $('tbody input.js-sel', gridParent).each(function(idx, el) {
                        $cb = $(this);
                        sel[ $cb.parents('tr').data('id') ] = action === 'upd_sel';
                    });
                    setSelection(sel);
                    break;
            }
            $(this).val('');
        });

        // handle each row multiselect toggles
        gridParent.on('change', 'tbody input.js-sel', function(ev) {
            var $cb = $(this), sel = {};
            sel[ $cb.parents('tr').data('id') ] = $cb.prop('checked');
            setSelection(sel);
        });

        // handle each row actions
        gridParent.on('change', 'tbody select.js-actions', function(ev) {
            var $select = $(this), $option = $($('option', $select).get(this.selectedIndex));
            var data = $option.data();
            if (data.href) {
                location.href = data.href;
            } else if (data.eval) {
                eval(data.eval);
            }
        });
        
        
    }
})
