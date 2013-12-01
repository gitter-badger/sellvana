function validationRules(rules) {
    var str = '';
    for(var key in rules) {
        switch(key) {
            case 'required':
                str+='data-rule-required="true" ';
                break;
            case 'number':
                str+='data-rule-number="true" ';
                break;
            case 'ip':
                str+='data-rule-ipv4="true" ';
                break;
            case 'url':
                str+='data-rule-url="true" ';
                break;
            case 'phoneus':
                str+='data-rule-phoneus="true" ';
                break;
            case 'minlength':
                str+='data-rule-minlength="'+rules[key]+'" ';
                break;
            case 'date':
                str+='data-rule-dateiso="true" data-mask="9999-99-99" placeholder="YYYY-MM-DD" ';
                break;
        }
    }

    return str;
}

define(['backbone', 'underscore', 'jquery', 'ngprogress', 'nestable', 'select2',
    'jquery.quicksearch', 'unique', 'jquery.validate', 'datetimepicker', 'jquery-ui', 'moment', 'daterangepicker'],
function(Backbone, _, $, NProgress) {

var setValidateForm = function(selector) {
    if (selector == null) {
      selector = $(".validate-form");
    }
    if (jQuery().validate) {
      return selector.each(function(i, elem) {
        return $(elem).validate({
          errorElement: "span",
          errorClass: "help-block has-error",
          errorPlacement: function(e, t) {
            return t.parents(".controls").first().append(e);
          },
          highlight: function(e) {
            return $(e).closest('.form-group').removeClass("has-error has-success").addClass('has-error');
          },
          success: function(e) {
            return e.closest(".form-group").removeClass("has-error");
          }
        });
      });
    }
};
FCom.BackboneGrid = function(config) {
    var rowsCollection;
    var columnsCollection;
    var gridView;
    var headerView;
    var filterView;
    var colsInfo;
    var selectedRows;
    var settings;
    var modalForm;
    var BackboneGrid = {
        Models: {},
        Collections: {},
        Views: {},
        currentState: {},
        colsInfo: {},
        data_mode: 'server',
        multiselect_filter: false

    }


    BackboneGrid.Models.ColModel = Backbone.Model.extend({
        defaults: {
            style: '',
            type: '',
            no_reorder: false,
            sortState: '',
            cssClass: '',
            hidden: false,
            label: '',
            href: '',
            cell: '',
            filtering: false,
            filterVal: '',
            selectedCount: 0,
            filterShow: true
        },
        initialize: function() {
            if (this.type === 'multiselect') {
                this.set('selectedCount',selectedRows.length);
            }
        }
    });

    BackboneGrid.Collections.ColsCollection = Backbone.Collection.extend({
        model: BackboneGrid.Models.ColModel,
        append: 1,
        comparator: function(col) {
            return parseInt(col.get('position'));
        },
        initialize: function() {
            this.on('add', this._addDefault, this);
            this.on('remove', this._removeDefault, this);
        },
        _addDefault: function(c) {
            if (typeof(c.get('default'))!=='undefined')
                BackboneGrid.Models.Row.prototype.defaults[c.get('name')] = c.get('default');
        },
        _removeDefault: function(c) {
            if (typeof(BackboneGrid.Models.Row.prototype.defaults[c.get('name')]) !== 'undefined')
                delete BackboneGrid.Models.Row.prototype.defaults[c.get('name')];
        }
    });

    BackboneGrid.Views.ThView = Backbone.View.extend({
        tagName: 'th',
        className: function() {
            var cssClass = this.model.get('cssClass');
            if (this.model.get('sortState').length >0) {
                cssClass += (' sorting_'+this.model.get('sortState'));
            }
            return cssClass;
        },
        attributes: function() {
            var hash = {};
            hash['data-id'] = this.model.get('name');
            if(this.model.has('width'))
                hash['style'] = "width: "+this.model.get('width')+'px;';
            if(this.model.has('overflow'))
                hash['style'] += hash['style'] +'overflow:hidden;'
            return hash;
        },
        events: {
            'click a': '_changesortState',
            'change select.js-sel': '_checkAction'
        },
        initialize: function() {
           // this.model.on('change', this, this);
           if (typeof(g_vent)!== 'undefined') {
                var self = this;
                g_vent.bind('clear_selection', function(ev) {
                    if (ev.grid === BackboneGrid.id)
                        self._clearSelection();
                });
           }

           this.model.on('render', this.render, this);
        },
        _selectPageAction: function(flag) {
            console.log('_selectPageAction');
            var temp = [];
            rowsCollection.each(function(model) {
                if (model.get('_selectable')) {
                    model.set('selected', flag);
                    temp.push(model.toJSON());
                }
            });

            if (flag) {
                selectedRows.reset(_.union(selectedRows.toJSON(),temp));
            } else {
                var ids = _.pluck(temp,'id');
                var newRows = [];
                console.log(temp);
                selectedRows.each(function (row) {
                    if (_.indexOf(ids, row.get('id')) === -1)
                        newRows.push(row.toJSON());
                });
                selectedRows.reset(newRows);
            }

            rowsCollection.each(function(model) {
                if (model.get('_selectable')) {
                    model.set('selected', flag);
                }
            });

            gridView.$el.find('input.select-row:not([disabled])').prop('checked', flag);
        },
        _checkAction: function(ev) {

            if ($(ev.target).val().indexOf('upd')!==-1)
                this._selectAction();
            else
                this._showAction();

            ev.stopPropagation();
            ev.preventDefault();

            return false;
        },
        //function to show All,Selected or Unselelected rows
        _showAction: function() {
            var key = this.$el.find('select.js-sel').val();
            switch (key) {
                case 'show_all':
                    console.log('show_all!!!');
                    if(BackboneGrid.showingSelected) {
                        BackboneGrid.data_mode = BackboneGrid.prev_data_mode;
                        rowsCollection.originalRows = BackboneGrid.prev_originalRows;
                        BackboneGrid.showingSelected = false;
                        if(BackboneGrid.data_mode !== 'local') {
                            $('.f-htmlgrid-toolbar.'+BackboneGrid.id+' div.pagination ul').css('display','block');
                            rowsCollection.fetch({reset:true});
                        } else {
                            rowsCollection.filter();
                        }

                    }
                    break;
                case 'show_sel':
                    if(!BackboneGrid.showingSelected) {
                        //console.log('show_sel!');
                        BackboneGrid.prev_data_mode = BackboneGrid.data_mode;
                        BackboneGrid.prev_originalRows = rowsCollection.originalRows;
                        BackboneGrid.showingSelected = true;
                        if(BackboneGrid.data_mode !== 'local')
                            $('.f-htmlgrid-toolbar.'+BackboneGrid.id+' div.pagination ul').css('display','none');

                        BackboneGrid.data_mode = 'local';
                        rowsCollection.originalRows = selectedRows;
                        rowsCollection.reset(selectedRows.models);
                        //console.log(selectedRows);
                    }
                    break;
            }

        },
        _clearSelection: function() {
            selectedRows.reset();
            rowsCollection.each(function(model) {
                if(model.get('selected'))
                    model.set('selected', false);
                //model.trigger('render');
            });
            gridView.$el.find('input.select-row:not([disabled])').prop('checked', false);
            $(BackboneGrid.MassDeleteButton).addClass('disabled');
            $(BackboneGrid.MassEditButton).addClass('disabled');
        },
        //function to select or unselect all rows of page and empty selected rows
        _selectAction: function() {
            var key = this.$el.find('select.js-sel').val();
            switch (key) {
                case 'upd_sel': //select all rows of a page
                    this._selectPageAction(true);
                    break;
                case 'upd_unsel'://unselect all rows of a page
                    this._selectPageAction(false);
                    break;
                case 'upd_clear': //empty selected rows collection
                    this._clearSelection();
                    break;
            }

            //this.model.set('selectedCount', selectedRows.length);
        },
        _changesortState: function(ev) {

            var status = this.model.get('sortState');

            if (status === '')
                status = 'asc';
            else if (status === 'asc')
                status = 'desc';
            else
                status = '';
            this.model.set('sortState', status);

            columnsCollection.each(function(m) {
                if(m.get('sortState') !== '' && m.get('name')!== this.model.get('name')) {
                    m.set('sortState', '');
                }
            }, this);

            BackboneGrid.currentState.s = status !=='' ? this.model.get('name') : '';
            BackboneGrid.currentState.sd = this.model.get('sortState');


            if(BackboneGrid.data_mode === 'local') {
                rowsCollection.sortLocalData();
                $.post( BackboneGrid.personalize_url,
                    {
                        'do': 'grid.state',
                        'grid': BackboneGrid.id,
                        's': BackboneGrid.currentState.s,
                        'sd': BackboneGrid.currentState.sd
                });
            } else {
                rowsCollection.fetch({reset: true});
                //gridView.render();
            }
            this.$el.attr('class', this.className());
            ev.preventDefault();
            headerView.render();

            return false;
        },
        render: function() {
            this.$el.html(this.template(this.model.toJSON()));
            this.$el.attr('class', this.className());

            return this;
        }
    });

    BackboneGrid.Views.HeaderView = Backbone.View.extend({
        initialize: function() {
            this.collection.on('sort', this.render, this);
            this.collection.on('render', this.render, this);
        },
        render: function() {
            //console.log('headerver_render');
            this.$el.html('');
            this.collection.each(this.addTh, this);
            gridParent = $('#'+BackboneGrid.id).parent();
            //this.$el.parents('table:first').colResizable();
            $('thead th', gridParent).resizable({
                handles: 'e',
                minWidth: 20,
                stop: function(ev, ui) {
                    var $el = ui.element, width = $el.width();
                    //$('tbody td[data-col="'+$el.data('id')+'"]', gridParent).width(width);
                    $.post(BackboneGrid.personalize_url,
                        { 'do': 'grid.col.width', grid: BackboneGrid.id, col: $el.data('id'), width: width },
                        function(response, status, xhr) {
                            //console.log(response, status, xhr);
                        }
                    );
                    colModel = columnsCollection.findWhere({name: $el.data('id')});
                    colModel.set('width', width);
                    //$(ev.target).append('<div class="ui-resizable-handle ui-resizable-e" style="z-index: 90;"></div>');
                    return true;
                }
            });

            return this;
        },
        addTh: function(ColModel) {
            //console.log(ColModel.get('hidden'));
            if (!ColModel.get('hidden')) {
                var th = new BackboneGrid.Views.ThView({model: ColModel});
                this.$el.append(th.render().el);
            }
        }
    });
    BackboneGrid.Models.Row = Backbone.Model.extend({
        defaults: {
            _actions: ' ',
            selected: false,
            editable: true,
            _selectable: true
        },
        initialize: function() {
            this.checkSelectVal();
            //this.on('change', this.checkSelectVal, this);
        },
        checkSelectVal: function() {
            var isSelected = typeof(selectedRows.findWhere({id: this.get('id')})) !== 'undefined';
            this.set('selected', isSelected);
        },
        destroy: function() {
            var id = this.get('id');
            if (typeof(g_vent) !== 'undefined' && BackboneGrid.events.indexOf('delete') !== -1) {
                var ev = {grid: BackboneGrid.id, id: id, row: this.toJSON()};
                g_vent.trigger('delete', ev);
            }

            if (typeof(BackboneGrid.edit_url) !== 'undefined' && BackboneGrid.edit_url.length>0) {
                var hash = {};
                hash.id = id;
                hash.oper = 'del';
                $.post(BackboneGrid.edit_url, hash);
            }

            return false;
        },
        save: function(not_render) {
            console.log('save');
            var self = this;
            var id = this.get('id');
            var hash = this.changedAttributes();
            hash.id = id;
            hash.oper = 'edit';
            if (typeof(g_vent) !== 'undefined' && _.indexOf(BackboneGrid.events, "edit") !== -1) {
                var row = this.toJSON();
                var ev = {grid: BackboneGrid.id, row: row};
                g_vent.trigger('edit', ev);
            }

            if (typeof(BackboneGrid.edit_url) !== 'undefined' && BackboneGrid.edit_url.length>0) {
                if(this.get('_new')) {
                    hash.oper = 'add';
                    $.post(BackboneGrid.edit_url, hash, function(data) {
                        self.set('id', data.id);
                        self.set('_new', false);
                    });
                } else {
                    $.post(BackboneGrid.edit_url, hash);
                }

            }
            if(!not_render)
                this.trigger('render');

            $(BackboneGrid.quickInputId).quicksearch('table#'+BackboneGrid.id+' tbody tr');
        }
    });


    BackboneGrid.Collections.Rows = Backbone.Collection.extend({
        model: BackboneGrid.Models.Row,
        initialize: function(models) {
            if (BackboneGrid.data_mode === 'local') {
                //console.log('collection initialize', models);
                this.originalRows = new Backbone.Collection(models);

                this.on('add', this.addInOriginal, this);
                this.on('remove', this.removeInOriginal, this);
            }

            if (typeof(g_vent) !== 'undefined') {
                    g_vent.bind('silent_inject', this._silentInjectRows);
                    g_vent.bind('add_row', this._addRow);
                    g_vent.bind('update_row', this._updateRow);
            }
        },
        _addRow: function(ev) {
            if (ev.grid === BackboneGrid.id) {
                var newRow = new BackboneGrid.Models.Row(ev.row);
                rowsCollection.add(newRow);
                gridView.render();
            }
        },
        _updateRow: function(ev) {
            if (ev.grid === BackboneGrid.id) {
                var rowModel = rowsCollection.get(ev.row.id);
                rowModel.set(ev.row);
                rowModel.save();
            }
        },
        _silentInjectRows: function(ev) {
            if (ev.grid !== BackboneGrid.id)
                return;
            //console.log(ev.rows);
            var rows = ev.rows;
            for (var i in rows) {
                if (typeof(rowsCollection.findWhere({id:rows[i].id})) === 'undefined') {
                    var row = new BackboneGrid.Models.Row(rows[i]);
                    rowsCollection.add(row);
                }

            }

            gridView.render();
        },
        filter: function() {

                var temp = this.originalRows.clone();
                for(var filter_key in BackboneGrid.current_filters) {

                    var filter_val = BackboneGrid.current_filters[filter_key];
                    var type = columnsCollection.findWhere({name: filter_key}).get('filter_type');

                    switch(type) {
                        case 'text':
                            var filterVal = filter_val.val+'';
                            var op = filter_val.op;
                            var check = {};
                            switch(op) {
                                case 'contains':
                                    check.contain = true;
                                    break;
                                case 'start':
                                    check.contain = true;
                                    check.start = true;
                                    break;
                                case 'end':
                                    check.contain = true;
                                    check.end = true;
                                    break;
                                case 'equal':
                                    check.contain = true;
                                    check.end = true;
                                    check.start = true;
                                    break;
                                case 'not':
                                    check.contain = false;
                                    break;
                            }
                            filterVal = filterVal.toLowerCase();
                            temp.models = _.filter(temp.models, function(model){
                                var flag = true;
                                var modelVal= model.get(filter_key)+'';
                                modelVal = modelVal.toLowerCase();
                                var first_index = modelVal.indexOf(filterVal);
                                var last_index = modelVal.lastIndexOf(filterVal);
                                for(key in check) {
                                    switch(key) {
                                        case 'contain':
                                            flag = flag && ((first_index!==-1) === check.contain);
                                            break;
                                        case 'start':
                                            flag = flag && first_index === 0;
                                            break;
                                        case 'end':
                                            flag = flag && (last_index+filterVal.length) === modelVal.length;
                                            break;
                                    }

                                    if(!flag)
                                        return flag;
                                }

                                return flag;
                            }, this);

                            break;
                        case 'multiselect':
                            filter_val = filter_val.split(',');
                            temp.models = _.filter(temp.models, function(model){

                                var flag = false;
                                for(var i in filter_val) {
                                    flag = flag || filter_val[i].toLowerCase() === model.get(filter_key).toLowerCase();
                                }

                                return flag;
                            }, this);


                            break;
                    }

                }
                //console.log(temp.models.length);
                this.reset(temp.models);
                gridView.render();
        },
        addInOriginal: function(model){
            this.originalRows.add(model);
            //console.log('add');
        },
        removeInOriginal: function(model){
            this.originalRows.remove(model);
            //console.log('remove');
        },
        sortLocalData: function() {
            if (BackboneGrid.currentState.s !=='' && BackboneGrid.currentState.sd !=='') {
                this.comparator = function(col) { return col.get(BackboneGrid.currentState.s); };
                if (BackboneGrid.currentState.sd === 'desc') {
                    this.comparator = this.reverseSortBy(this.comparator);
                }
                this.sort();
                gridView.render();
            } else {
                //console.log(rowsCollection.originalRows);
                this.reset(this.originalRows.models);
                gridView.render();
            }
        },
        url: function() {
            var append = '';
            var keys = ['p', 's', 'sd', 'ps'];
            for (var i in keys) {
                if( append != '')
                    append += '&';
                append += (keys[i]+'='+BackboneGrid.currentState[keys[i]]);
            }
            append += ('&filters='+JSON.stringify(BackboneGrid.current_filters));
            var c = this.data_url.indexOf('?') === -1 ? '?' : '&';
            return this.data_url+c+append+'&gridId='+BackboneGrid.id;
        },
        parse: function(response) {
            if (typeof(response[0].c) !== 'undefined') {
                if (response[0].c !== BackboneGrid.currentState.c) {
                    var mp = Math.ceil(response[0].c / BackboneGrid.currentState.ps) ;
                    BackboneGrid.currentState.mp = mp;
                    BackboneGrid.currentState.c = response[0].c;
                    if (BackboneGrid.data_mode !== 'local')
                        updatePageHtml();
                }
            }
            return response[1];
        },
        reverseSortBy: function(sortByFunction) {
          return function(left, right) {
            var l = sortByFunction(left);
            var r = sortByFunction(right);

            if (l === void 0) return -1;
            if (r === void 0) return 1;

            return l < r ? 1 : l > r ? -1 : 0;
          };
        }
    });

    BackboneGrid.Views.RowView = Backbone.View.extend({
        tagName: 'tr',
        className: function() {
            return this.model.get('cssClass');
        },
        attributes: function() {
            return {id: this.model.get('id')};
        },
        events: {
            'change input.select-row': '_selectRow',
            'change .form-control': '_cellValChanged',
            //'blur .form-control': '_validate',
            'click button.btn-delete': '_deleteRow',
            'click button.btn-edit._modal': '_editModal',
            'click button.btn-custom': '_callbackCustom'
        },
        initialize: function() {
            this.model.on('render',this.render, this);
            this.model.on('remove', this._destorySelf, this);

            //this.model.on('change', this.render, this);
        },
        _callbackCustom: function(ev) {
            if (typeof(g_vent) !== 'undefined') {
                g_vent.trigger('custom_callback', {grid: BackboneGrid.id, row:this.model.toJSON()});
                ev.stopPropagation();
                ev.preventDefault();

                return false;
            }
        },
        _editModal: function(ev) {
            modalForm.modalType = 'editable';
            BackboneGrid.currentRow = this.model;
            console.log(BackboneGrid.currentRow.toJSON());
            modalForm.render();
            $(BackboneGrid.modalShowBtnId).trigger('click');
            return true;
        },
        _validate: function(ev) {

            var val = $(ev.target).val();
            var name = $(ev.target).attr('data-col');
            var col = columnsCollection.findWhere({name: name});
            if (typeof(col) != 'undefined') {
                if (typeof(col.get('validation')) !== 'undefined') {
                    var validation = col.get('validation');
                    if(validation.number) {
                        if (isNaN(val))
                            $(ev.target).addClass('unvalid');
                        else
                            $(ev.target).removeClass('unvalid');

                        return  !isNaN(val);
                    }
                    if(validation.required) {

                        var status = (val === '' || typeof(val) === 'undefined');
                        if (status)
                            $(ev.target).addClass('unvalid');
                        else
                            $(ev.target).removeClass('unvalid');

                        return  !status;
                    }
                }
            }

            return true;

        },
        _selectRow: function(ev) {
            var checked = $(ev.target).is(':checked');
            this.model.set('selected',checked);

            if (checked) {
                selectedRows.add(this.model);
            } else {
                selectedRows.remove(this.model,{silent:true});
                selectedRows.trigger('remove');

                if (BackboneGrid.showingSelected) {
                    rowsCollection.remove(this.model,{silent:true});
                    //gridView.render();
                }
            }
            ev.stopPropagation();
            ev.preventDefault();

            return;

        },
        _cellValChanged: function(ev) {
            var val = $(ev.target).val();
            var name = $(ev.target).attr('data-col');

            //if(!this._validate(ev))
            //{
                //console.log('validate fail');
                /*if(typeof(g_vent) != 'undefined') {
                    g_vent.trigger('validate_fail',{
                                                        grid:BackboneGrid.id,
                                                        data: {
                                                                name:name,
                                                                val:val,
                                                                id:this.model.get('id')
                                                            }
                                                    }
                                  );
                }*/
            //    return;
            //}
            this.model.set(name, val);
            this.model.save(true);

        },
        _deleteRow: function(ev) {
            var confirm;
            if ($(ev.target).hasClass('noconfirm'))
                confirm = true;
            else
                confirm = window.confirm("Do you want to really delete?");

            if (confirm) {
                rowsCollection.remove(this.model, {silent: true});
                selectedRows.remove(this.model, {silent: true});
                this._destorySelf();
            }
        },
        _destorySelf: function() {
            this.undelegateEvents();
            this.$el.removeData().unbind();
            this.remove();
            this.model.destroy();
        },
        render: function() {
            console.log('row-render');
            var colsInfo = columnsCollection.toJSON();
            this.$el.html(this.template({row:this.model.toJSON(), colsInfo: colsInfo}));

            if (typeof(BackboneGrid.callbacks['after_render']) !== 'undefined') {
                console.log('after_render');
                var func = BackboneGrid.callbacks['after_render'];
                var script = func+'(this.$el,this.model.toJSON());';
                eval(script);
            }
            return this;
        },
        setValidation: function() {
            var self = this;
            columnsCollection.each(function(col) {
                if (col.get('editor') === 'select' && col.get('editable') === 'inline') {
                    var name = col.get('name');
                    self.$el.find('select#'+name).val(self.model.get(name));
                }

                if (col.get('editable') === 'inline') {
                    var name = col.get('name');
                    var editor = col.get('editor') === 'select' ? 'select' : 'input';
                }
            });


        }
    });

    BackboneGrid.Views.GridView = Backbone.View.extend({
      //  el: 'table tbody',
        initialize: function () {
            this.collection.on('reset', this.render, this);
            this.collection.on('render', this.render, this);
            this.collection.on('add', this.addRow, this);
        },
        updateColsAndRender: function() {
            this.render();
        },
        setCss: function() {
            var models = this.collection.models;
            for(var i in models) {
                var cssClass = i % 2 == 0 ? 'even' : 'odd';
                models[i].set('cssClass', cssClass);
            }
        },
        getMainTable : function() {
            return $('#'+BackboneGrid.id);
        },
        render: function() {
            //console.log('gridview-render');
            this.setCss();
            this.$el.html('');
            this.collection.each(this.addRow, this);
            $(BackboneGrid.quickInputId).quicksearch('table#'+BackboneGrid.id+' tbody tr');
            return this;
        },
        addRow: function(row) {
            var rowView = new BackboneGrid.Views.RowView({
                model: row
            });
            this.$el.append(rowView.render().el);
            rowView.setValidation();
        }
    });
    BackboneGrid.Views.ColCheckView = Backbone.View.extend({
        tagName: 'li',
        className: 'dd-item dd3-item',
        attributes: function () {
            return {'data-id': this.model.get('name')};
        },
        events: {
            'change input.showhide_column': 'changeState',
            'click input.showhide_column': 'preventDefault',
            'click .dd3-content': 'preventDefault'
        },
        preventDefault: function(ev){

            ev.stopPropagation();
        },
        changeState: function(ev) {

            this.model.set('hidden',!this.model.get('hidden'));
            headerView.render();
            filterView.render();

            var name = 'hidden'+this.model.get('name');
            var value = this.model.get('hidden');
            gridView.collection.each(function(row) {
                row.set(name, value);
            });

            $.post(BackboneGrid.personalize_url,{
                'do': 'grid.col.hidden',
                'col': this.model.get('name'),
                'hidden': value,
                'grid': columnsCollection.grid
            });
            gridView.render();

            ev.stopPropagation();
            return false;
        },
        render: function() {
            this.$el.html(this.template(this.model.toJSON()));
            return this;
        }
    });
    BackboneGrid.Views.ColsVisibiltyView = Backbone.View.extend({
        initialize: function() {
            this.setElement('#'+BackboneGrid.id+' .dd-list');
            this.collection.on('render', this.render, this);
        },
        orderChanged: function(ev) {
            //console.log('orderChanged');
            var orderJson = $('.'+BackboneGrid.id+'.dd').nestable('serialize');
            var changedFlag = false;
            for(var i in orderJson) {
                var key = orderJson[i].id;
                colModel = columnsCollection.findWhere({name: key});
                if (parseInt(colModel.get('position')) !== parseInt(i)+columnsCollection.append) {
                    colModel.set('position', parseInt(i)+columnsCollection.append);
                    changedFlag = true;
                }

            }

            if (!changedFlag)
                return;

            columnsCollection.sort();
            gridView.render();

             $.post(BackboneGrid.personalize_url,{
                'do': 'grid.col.orders',
                'cols': columnsCollection.toJSON(),
                'grid': columnsCollection.grid
            });


        },
        render: function() {
            this.$el.html('');
            this.collection.each(this.addLiTag, this);

            // not working
            /*this.$el.find('.dd:first').nestable().on('change',function(){
            });*/

            // working
            $('.'+BackboneGrid.id+'.dd').nestable().on('change',this.orderChanged);
        },
        addLiTag: function(model) {
            if(model.get('label') !== '') {
                var checkView = new BackboneGrid.Views.ColCheckView({model:model});
                this.$el.append(checkView.render().el);
            }
        }
    });
    BackboneGrid.Views.FilterCell = Backbone.View.extend({
        className: 'btn-group dropdown f-grid-filter',

        _filter: function(val) {
            if (val === false) {
                this.$el.removeClass('f-grid-filter-val');
                this.model.set('filterVal','');
                if(typeof(BackboneGrid.current_filters[this.model.get('name')]) === 'undefined')
                    return;
                delete BackboneGrid.current_filters[this.model.get('name')];
                this.render();
            } else {
                this.$el.addClass('f-grid-filter-val');
                if (val.length === 0)
                    delete BackboneGrid.current_filters[this.model.get('name')];
            }

            if (BackboneGrid.data_mode === 'local') {
                rowsCollection.filter();
            } else {
                rowsCollection.fetch({reset:true});
            }

            if (typeof(this.updateMainText) !== 'undefined')
                this.updateMainText();
        },
        preventDefault: function(ev) {
                ev.preventDefault();
                ev.stopPropagation();

                return false;
        },
        render: function() {
            this.$el.html(this.template(this.model.toJSON()));
            this.$el.append('<abbr class="select2-search-choice-close"></abbr>');

            var self = this;
            this.$el.find('abbr').click(function(ev) {
                self._filter(false);
            });

            return this;
        },
        updateMainText: function() {
            var html = this.model.get('label')+': ';
            if (this.model.get('filterVal') ==='') {
                html += 'All';
            } else {
                html += (this.model.get('filterLabel') +' "'+this.model.get('filterVal')+'"');
            }
            html += '<span class="caret"></span>';
            this.$el.find('button.filter-text-main').html(html);
        }
    });
    BackboneGrid.Views.FilterTextCell = BackboneGrid.Views.FilterCell.extend({
        events: {
            'click input': 'preventDefault',
            'click button.update': 'filter',
            'click .filter-text-sub': 'subButtonClicked',
            'click a.filter_op': 'filterOperatorSelected',
            'keyup input': '_checkEnter'
        },
        initialize: function() {
            this.model.set('filterOp', 'contains');
        },
        _checkEnter: function(ev) {
            var evt = ev || window.event;
            var charCode = evt.keyCode || evt.which;
            if (charCode === 13) {
                this.$el.find('button.update').trigger('click');
            }
        },

        filterOperatorSelected: function(ev) {
            //this.filterValChanged();
            var operator = $(ev.target);
            this.model.set('filterOp',operator.attr('data-id'));
            this.model.set('filterLabel', operator.html());
            this.$el.find('button.filter-text-sub').html(operator.html()+"<span class='caret'></span>");
            this.$el.find('button.filter-text-sub').parents('div.dropdown:first').toggleClass('open');

            return false;
        },
        subButtonClicked: function(ev) {
            this.$el.find('button.filter-text-sub').parents('div.dropdown:first').toggleClass('open');

            return false;
        },
        filter: function() {
            var field = this.model.get('name');
            var filterVal = this.$el.find('input:first').val();
            var op = this.model.get('filterOp');
            BackboneGrid.current_filters[field] = {val: filterVal, op: op};
            this.model.set('filterVal',filterVal);
            this._filter(filterVal);

        }

    });

    BackboneGrid.Views.FilterDateRangeCell = BackboneGrid.Views.FilterCell.extend({
        events: {
            'click input': 'preventDefault',
            'click button.update': 'filter',
            //'click .filter-box': 'preventDefault',
            'click .filter-text-sub': 'subButtonClicked',
            'click a.filter_op': 'filterOperatorSelected'
        },
        initialize: function() {
            this.range = true;
            this.model.set('filterOp', 'between');
        },
        filterOperatorSelected: function(ev) {
            this.range = $(ev.target).hasClass('range');
            if (this.range) {
                this.$el.find('div.range').css('display','table');
                this.$el.find('div.not_range').css('display','none');
            } else {
                this.$el.find('div.range').css('display','none');
                this.$el.find('div.not_range').css('display','table');
            }

            var operator = $(ev.target);
            this.model.set('filterOp',operator.attr('data-id'));
            this.model.set('filterLabel', operator.html());
            this.$el.find('button.filter-text-sub').html(operator.html()+"<span class='caret'></span>");
            this.$el.find('button.filter-text-sub').parents('div.dropdown:first').toggleClass('open');

            return false;
        },
        subButtonClicked: function(ev) {
            this.$el.find('button.filter-text-sub').parents('div.dropdown:first').toggleClass('open');

            return false;
        },
        filter: function() {
            var field = this.model.get('name');
            var filterVal;
            if (this.range)
                filterVal = this.$el.find('input:first').val();
            else
                filterVal = this.$el.find('input:last').val();

            var op = this.model.get('filterOp');
            BackboneGrid.current_filters[field] = {val: filterVal, op: op};
            this.model.set('filterVal',filterVal);
            this._filter(filterVal);

        },
        render: function() {
            BackboneGrid.Views.FilterCell.prototype.render.call(this);
            var self = this.$el;
            this.$el.find('#daterange2').daterangepicker({
                                            format: "YYYY-MM-DD"
                                          }, function(start, end) {
                                            return self.find("#daterange2").parent().find("input").first().val(start.format("YYYY-MM-DD") + "~" + end.format("YYYY-MM-DD"));
                                          });
            this.$el.find(".datepicker").datetimepicker({
                pickTime: false
            });

            $('div.daterangepicker').on('click', function(ev) {
                                                            ev.stopPropagation();
                                                            ev.preventDefault();

                                                            return false;
                                                        }
                                    );

            var filterVal = this.model.get('filterVal');
            if (this.range)
                filterVal = this.$el.find('input:first').val(filterVal);
            else
                filterVal = this.$el.find('input:last').val(filterVal);

            return this;
        }
    });
    BackboneGrid.Views.FilterNumberRangeCell = BackboneGrid.Views.FilterDateRangeCell.extend({
        render: function() {
            BackboneGrid.Views.FilterCell.prototype.render.call(this);

            return this;
        },
        filter: function() {
            var field = this.model.get('name');
            var filterVal;
            if (this.range)
                filterVal = this.$el.find('input.js-number1').val()+'~'+this.$el.find('input.js-number2').val();
            else
                filterVal = this.$el.find('input.js-number').val();

            var op = this.model.get('filterOp');
            BackboneGrid.current_filters[field] = {val: filterVal, op: op};
            this.model.set('filterVal',filterVal);
            this._filter(filterVal);
        }
    });
    BackboneGrid.Views.FilterMultiselectCell = BackboneGrid.Views.FilterCell.extend({
        events: {
            'click button.update': 'filter',
            'focusin div.select2-container': '_preventClose'
        },
        _preventClose: function() {
            this.$el.addClass('js-prevent-close');
            this.$el.find('ul.filter-box').css('display','block');
        },
        filter: function(val) {
            this.$el.removeClass('js-prevent-close');
            this.$el.find('ul.filter-box').css('display','');
            val = this.$el.find('#multi_hidden:first').val();
            if (val === '') {
                if (this.model.get('filterVal') !== '') {

                    this.model.set('filterVal', '');
                    this._filter(false);
                } else {
                    this.model.set('filterVal', '');
                    this.render();

                }

                return;

            }

            BackboneGrid.current_filters[this.model.get('name')] = val;
            this._filter(val);

            var html = this.model.get('label')+': '+val;
            html += '<span class="caret"></span>';
            this.$el.find('button.filter-text-main').html(html);
        },
        render: function() {
            BackboneGrid.Views.FilterCell.prototype.render.call(this);

            var options = this.model.get('_multipulFilterOptions');
            var data = [];
            for(var key in options) {
                data[data.length] = {id: key, text: options[key]};
            }

            this.$el.find('#multi_hidden:first').select2({
                multiple: true,
                data: data,
                placeholder: 'All'
                //closeOnSelect: true
            });

            var self = this;
            this.$el.find('#multi_hidden:first').change(function(ev) {
                self._preventClose();
            })
            return this;
        }

    });

    BackboneGrid.Views.FilterSelectCell = Backbone.View.extend({
        className: 'btn-group dropdown f-grid-filter',
        _changeCss: function() {
            this.$el.find('div.select2-container').addClass('btn-group');
            this.$el.find('div.select2-container a').removeClass('select2-default');
        },
        filter: function(val) {
            BackboneGrid.current_filters[this.model.get('name')] = val;
            BackboneGrid.Views.FilterCell.prototype._filter.call(this, val);
        },
        render: function() {
            this.$el.html(this.template(this.model.toJSON()));
            var fieldLabel = this.model.get('label');
            var options = this.model.get('_multipulFilterOptions');
            var data = [];
            for(var key in options) {
                data[data.length] = {id: key, text: options[key]};
            }
            this.$el.find('#select_hidden:first').select2({
                //multiple: this.model.get('filter_type') === 'select' ? false : true,
                data: data,
                placeholder: fieldLabel +': All',
                allowClear: true
                //closeOnSelect: true
            });

            var self = this;
            this.$el.find('#select_hidden:first').on('change', function() {
                var val = $(this).val();

                if (val !== '') {
                    var temp = self.$el.find('div.select2-container span.select2-chosen');
                    temp.html(fieldLabel+': '+temp.html());

                } else {
                    self.$el.find('div.select2-container a').removeClass('select2-default');
                }

                self.filter(val);
            });

            this._changeCss();

            return this;
        }

    });

    BackboneGrid.Views.FilterView = Backbone.View.extend({
        initialize: function() {
            var div = 'div.row.datatables-top.'+BackboneGrid.id+' .f-filter-btns';
            this.setElement(div);
            this.collection.on('sort', this.render, this);
        },
        render: function() {
            this.$el.html('');
            this.collection.each(this.addFilterCol, this);
        },
        addFilterCol: function(model) {

            if(model.get('hidden') !== true && model.get('filtering') && model.get('filterShow')) {
                var filterCell;
                switch (model.get('filter_type')) {
                    case 'text':
                        filterCell = new BackboneGrid.Views.FilterTextCell({model:model});
                        break;
                    case 'date-range':
                        filterCell = new BackboneGrid.Views.FilterDateRangeCell({model:model});
                        break;
                    case 'number-range':
                        filterCell = new BackboneGrid.Views.FilterNumberRangeCell({model:model});
                        break;
                    case 'multiselect':
                        BackboneGrid.multiselect_filter = true;
                        filterCell = new BackboneGrid.Views.FilterMultiselectCell({model:model});
                        break;
                    case 'select':
                        filterCell = new BackboneGrid.Views.FilterSelectCell({model:model});
                        break;
                }
                this.$el.append(filterCell.render().el);
            }
        }
    });

    BackboneGrid.Views.ModalElement = Backbone.View.extend({
        className: 'form-group',
        render: function() {
            this.$el.html(this.template(this.model.toJSON()));

            return this;
        }
    });
    BackboneGrid.Views.ModalForm = Backbone.View.extend({
        initialize: function() {
            this.modalType = 'mass-editable';
        },
        _saveChanges: function(ev) {
            modalForm.$el.find('input, select').each(function() {
                var key = $(this).attr('id');
                var val = $(this).val();
                BackboneGrid.modalElementVals[key] = val;
            });

            if (!modalForm.formEl.valid())
                return;

            for( var key in BackboneGrid.modalElementVals) {
                if (BackboneGrid.modalElementVals[key] === '')
                    delete BackboneGrid.modalElementVals[key];
            }
            if (modalForm.modalType === 'mass-editable') {
                var ids = selectedRows.pluck('id').join(",");

                if (typeof(g_vent) !== 'undefined' && _.indexOf(BackboneGrid.events, "mass-edit") !== -1) {
                    var rows = selectedRows.toJSON();
                    for  (var i in rows) {
                        for(var key in BackboneGrid.modalElementVals)
                            rows[i][key] = BackboneGrid.modalElementVals[key];
                    }
                    var evt = {grid: BackboneGrid.id, rows: rows};
                    g_vent.trigger('mass-edit', evt);
                }

                if (typeof(BackboneGrid.edit_url) !== 'undefined' && BackboneGrid.edit_url.length>0) {
                    var hash = BackboneGrid.modalElementVals;
                    hash.id = ids;
                    hash.oper = 'mass-edit';
                    $.post(BackboneGrid.edit_url, hash)
                    .done(function(data) {
                        $.bootstrapGrowl("Successfully saved.", { type:'success', align:'center', width:'auto' });
                    });
                    delete BackboneGrid.modalElementVals.id;
                    delete BackboneGrid.modalElementVals.oper;
                }

                selectedRows.each(function(model) {
                    for(var key in BackboneGrid.modalElementVals) {
                        model.set(key, BackboneGrid.modalElementVals[key]);
                        model.trigger('render');
                    }
                });

            }

            if (modalForm.modalType === 'addable') {
                var hash = BackboneGrid.modalElementVals;
                if (typeof(BackboneGrid.edit_url) !== 'undefined' && BackboneGrid.edit_url.length>0) {
                    hash.oper = 'add';
                    $.post(BackboneGrid.edit_url, hash, function(data) {
                        var newRow = new BackboneGrid.Models.Row(data);
                        rowsCollection.add(newRow);
                        //gridView.addRow(newRow);
                    });
                } else {
                    hash.id = guid();
                    var newRow = new BackboneGrid.Models.Row(hash);
                    rowsCollection.add(newRow);
                    //gridView.addRow(newRow);
                }

                if (typeof(g_vent) !== 'undefined' && BackboneGrid.events.indexOf('new') !== -1) {
                    if (typeof(hash.oper) !== 'undefined')
                        delete hash.oper;
                    hash._new = true;
                    g_vent.trigger('new', {grid: BackboneGrid.id, row: hash});
                }
            }

            if (modalForm.modalType === 'editable') {
                for (key in BackboneGrid.modalElementVals) {
                    BackboneGrid.currentRow.set(key, BackboneGrid.modalElementVals[key]);
                    BackboneGrid.currentRow.save();
                }
            }

            $(ev.target).prev().trigger('click');
            BackboneGrid.modalElementVals = {};

        },
        initialize: function() {
            //this.collection.on('sort change reset', this.render, this);
            this.$el.parents('div.modal-dialog:first').find('button.save').click(this._saveChanges);
        },
        render: function() {
            this.$el.html('');
            var header;
            switch(this.modalType) {
                case 'addable':
                    header = 'Create Form';
                    BackboneGrid.currentRow = false;
                    break;
                case 'mass-editable':
                    BackboneGrid.currentRow = false;
                    header = 'Mass Edit Form';
                    break;
                case 'editable':
                    header = 'Edit Form';
                    break;
            }
            $(BackboneGrid.modalFormId).find('h4').html(header);
            BackboneGrid.modalElementVals = {};
            this.collection.each(this.addElementDiv, this);

            /*if (this.modalType === 'addable' || this.modalType ==='mass-editable')
                $(BackboneGrid.modalFormId).find('select').val('');*/
            if (this.modalType ==='mass-editable')
                $(BackboneGrid.modalFormId).find('select').val('');

            this.formEl = this.$el.parents('form:first');
            setValidateForm(this.formEl);

            this.collection.each(function(col) {

                if (typeof(col.get('validation')) !== 'undefined' && typeof(col.get('validation').unique) !== 'undefined') {
                    var url = col.get('validation').unique;
                    modalForm.$el.find('#'+col.get('name')).rules("add", {
                        onfocusout: false,
                        onkeyup: false,
                        remote: {
                            url: url,
                            type: 'post',
                             data: {
                                name: col.get('name')
                            },
                            dataFilter: function (responseString) {
                                var response = jQuery.parseJSON(responseString);
                                currentMessage = response.Message;
                                if (modalForm.modalType === 'editable' && BackboneGrid.currentRow.get('id') === response.id)
                                    return true;
                                return response.unique;
                            }
                            //async:false
                        },
                        messages: {
                            remote: "This "+col.get('label')+" is already taken place"
                        }
                    });
                }
            });

        },
        addElementDiv: function(model) {
            if (model.has(this.modalType) && model.get(this.modalType)) {
                var elementView = new BackboneGrid.Views.ModalElement({model: model});
                this.$el.append(elementView.render().el);

                if(this.modalType === 'mass-editable') {
                    elementView.$el.find('#'+model.get('name')).removeAttr('data-rule-required');
                }

                if (BackboneGrid.currentRow) {
                    var name = model.get('name');
                    var val = (typeof(BackboneGrid.currentRow.get(name)) !== 'undefined' ? BackboneGrid.currentRow.get(name) : '');
                    console.log(val);
                    elementView.$el.find('input,select:last').val(val);
                }
            }
        }
    });
    function updatePageHtml(p,mp) {

            p = BackboneGrid.currentState.p;
            mp = BackboneGrid.currentState.mp;
            //console.log(BackboneGrid.currentState.mp);
            var html = '';

            html += '<li class="first'+ (p<=1 ? ' disabled' : '')+'">';
            html += '<a class="js-change-url" href="#">&laquo;</a>';
            html += '</li>';

            html += '<li class="prev'+ (p<=1 ? ' disabled' : '')+'">';
            html += '<a class="js-change-url" href="#">&lsaquo;</a>';
            html += '</li>';


            for (var i= Math.max(p-3,1); i<=Math.min(p+3,mp);i++) {
                html += '<li class="page'+(i == p ? ' active' : '')+'">';
                html += '<a class="js-change-url" data-page="" href="#">'+ i +'</a>';
                html += '</li>';
            }

            html += '<li class="next'+ (p>=mp ? ' disabled' : '')+'">';
            html += '<a class="js-change-url" href="#">&rsaquo;</a>';
            html += '</li>';

            html += '<li class="last'+ (p>=mp ? ' disabled' : '')+'">';
            html += '<a class="js-change-url" href="#">&raquo;</a>';
            html += '</li>';


            $('ul.'+BackboneGrid.id+'.pagination.page').html(html);

            var caption = '';
            if (BackboneGrid.currentState.c > 0)
                caption = 'Page: '+p+' of '+mp+' | '+BackboneGrid.currentState.c+' records';
            else
                caption = 'No data';
            $('span.'+BackboneGrid.id+'-pagination').html(caption);
    }

        NProgress.start();
        //general settings
        _.templateSettings.variable = 'rc';
        BackboneGrid.id = config.id;
        BackboneGrid.personalize_url = config.personalize_url;
        BackboneGrid.edit_url = config.edit_url;
        BackboneGrid.data_url = config.data_url;
        BackboneGrid.current_filters= {};
        BackboneGrid.quickInputId = '#'+config.id+'-quick-search';
        BackboneGrid.events = config.events;
        BackboneGrid.callbacks = config.callbacks;
        BackboneGrid.modalShowBtnId = '#'+config.id+'-modal-form-show';
        BackboneGrid.modalFormId = '#'+config.id+'-modal-form';
        //personal settings
        var state = config.data.state;
        state.p = parseInt(state.p);
        state.mp = parseInt(state.mp);
        BackboneGrid.currentState = state;

        //check data mode
        if(config.data_mode) {
            BackboneGrid.data_mode = config.data_mode;
        }

        //theader
        BackboneGrid.Collections.ColsCollection.prototype.grid = config.id;
        BackboneGrid.Models.ColModel.prototype.personalize_url = config.personalize_url;

        BackboneGrid.Views.ThView.prototype.template = _.template($('#'+config.id+'-header-template').html());
        BackboneGrid.Views.HeaderView.prototype.el = "#"+config.id+" thead tr";
        //tbody
        BackboneGrid.Views.GridView.prototype.el = "table#"+config.id+" tbody";
        BackboneGrid.Views.RowView.prototype.template = _.template($('#'+config.id+'-row-template').html());
        BackboneGrid.Collections.Rows.prototype.data_url = config.data_url;

        //filtering settings
        BackboneGrid.Views.FilterTextCell.prototype.template = _.template($('#'+config.id+'-text-filter-template').html());
        BackboneGrid.Views.FilterDateRangeCell.prototype.template = _.template($('#'+config.id+'-date-range-filter-template').html());
        BackboneGrid.Views.FilterSelectCell.prototype.template = _.template($('#'+config.id+'-select-filter-template').html());
        BackboneGrid.Views.FilterMultiselectCell.prototype.template = _.template($('#'+config.id+'-multiselect-filter-template').html());
        BackboneGrid.Views.FilterNumberRangeCell.prototype.template =_.template($('#'+config.id+'-number-range-filter-template').html());
        //column visiblity checkbox view
        BackboneGrid.Views.ColCheckView.prototype.template = _.template($('#'+config.id+'-col-template').html());

        //mass edit modal view
        BackboneGrid.Views.ModalForm.prototype.el = BackboneGrid.modalFormId+" div.modal-body";
        BackboneGrid.Views.ModalElement.prototype.template = _.template($('#'+config.id+'-modal-element-template').html());


        /*if (BackboneGrid.data_mode === 'local') {
            state.mp = config.data.data.length;
        }*/
        if (config.data_mode != 'local') {
            $('ul.pagination.page').on('click', 'li', function(ev) {
                var li = $(this);
                if(li.hasClass('first'))
                    BackboneGrid.currentState.p = 1;
                if(li.hasClass('next'))
                    BackboneGrid.currentState.p ++;
                if(li.hasClass('prev'))
                    BackboneGrid.currentState.p --;
                if(li.hasClass('last'))
                    BackboneGrid.currentState.p = BackboneGrid.currentState.mp;
                if(li.hasClass('page'))
                    BackboneGrid.currentState.p = parseInt(li.find('a').html());
                updatePageHtml();
                rowsCollection.fetch({reset: true});
                ev.preventDefault();
                return;
            });

            updatePageHtml();
        }
        //header view
        var columns = config.columns;
        columnsCollection = new BackboneGrid.Collections.ColsCollection;
        var filters = config.filters;
        for (var i in columns) {
            var c = columns[i];
            //if (c.name != 'id') {
                if (c.hidden === 'false')
                    c.hidden = false;
                if (c.name === 0) {
                    columnsCollection.append = 2;
                }

                c.id = config.id+'-'+c.name;
                //c.style = c['width'] ? "width:"+c['width']+"px" : '';

                c.cssClass = '';
                if (!c['no_reorder'])
                    c.cssClass += 'js-draggable ';

                if (state['s'] && c['name'] && state['s'] == c['name']) {
                    //c.cssClass += 'sort-'+state['sd']+' ';
                    c.sortState = state['sd'];
                } else {
                    //c.cssClass += 'sort';
                    c.sortState = "";
                }
                var filter = _.findWhere(filters, {field: c.name});
                if (typeof(filter) !== 'undefined') {
                    c.filtering = true;
                    c.filter_type = filter['type'];

                    if (filter.type === 'text') {
                        c.filterOp = 'contains';
                        c.filterLabel = 'Contains';
                    }

                    if (filter.type === 'date-range' || filter.type === 'number-range') {
                        c.filterOp = 'Between';
                        c.filterLabel = 'Between';
                    }

                    if (filter.type === 'multiselect' || filter.type === 'select') {
                        if(typeof(filter.options) !== 'undefined') {
                            c._multipulFilterOptions = filter.options;
                        } else if( typeof(c.options) !== 'undefined') {
                            c._multipulFilterOptions = c.options;
                        }
                    }
                }
                if (BackboneGrid.validation !== true && typeof(c.validation) !== 'undefined')
                    BackboneGrid.validation = true;
                if (typeof(c.default) !== 'undefined') {
                    BackboneGrid.Models.Row.prototype.defaults[c.name] = c.default;
                } else {
                    BackboneGrid.Models.Row.prototype.defaults[c.name] = '';
                }
                var ColModel = new BackboneGrid.Models.ColModel(c);
                columnsCollection.add(ColModel);
           // }
        }

        headerView = new BackboneGrid.Views.HeaderView({collection: columnsCollection});
        headerView.render();
        var colsVisibiltyView = new BackboneGrid.Views.ColsVisibiltyView({collection: columnsCollection});
        colsVisibiltyView.render();
        filterView = new BackboneGrid.Views.FilterView({collection: columnsCollection});
        filterView.render();
        if (BackboneGrid.multiselect_filter) {
            $('body').click(function(ev) {
                var _cache = filterView.$el.find('div.js-prevent-close');
                // checking whether opened multiselect filter is exist and clicked element is not opend multilselect filter div
                if (_cache.length > 0 && $(ev.target).parents('div.js-prevent-close').length === 0) {
                    _cache.find('ul.filter-box').css('display', '');
                    _cache.removeClass('js-prevent-close');
                }
            });
        }
        //body view
        var rows = config.data.data;
        rowsCollection = new BackboneGrid.Collections.Rows;

        //showing selected rows count
        selectedRows = new Backbone.Collection;
        var multiselectCol = columnsCollection.findWhere({type: 'multiselect'});
        selectedRows.on('add remove reset',function(){
            multiselectCol.set('selectedCount', selectedRows.length);
            multiselectCol.trigger('render');
            if (selectedRows.length > 0) {
                $(BackboneGrid.MassDeleteButton).removeClass('disabled');
                $(BackboneGrid.MassEditButton).removeClass('disabled');
            } else {
                $(BackboneGrid.MassDeleteButton).addClass('disabled');
                $(BackboneGrid.MassEditButton).addClass('disabled');
            }

            if (typeof(g_vent) !== 'undefined' && BackboneGrid.events.indexOf('select-rows') !== -1) {
                g_vent.trigger('select-rows', {grid: config.id, rows: selectedRows.toJSON()});
            }
        });

        for (var i in rows) {

            var rowModel = new BackboneGrid.Models.Row(rows[i]);
            rowsCollection.add(rowModel);
        }

        gridView = new BackboneGrid.Views.GridView({collection: rowsCollection});

        if (BackboneGrid.data_mode === 'local' && BackboneGrid.currentState.s !=='' && BackboneGrid.currentState.s!=='') {
            rowsCollection.sortLocalData();
        }

        gridView.render();
        //local rows count info
        if (BackboneGrid.data_mode === 'local') {
            var pageSpan = $('span.'+BackboneGrid.id+'-pagination.f-grid-pagination');
            pageSpan.css('top', 10);
            function setLocalPageInfo()
            {
                if (rowsCollection.length === 0) {
                    pageSpan.html('No data.');
                } else {
                    pageSpan.html(rowsCollection.length+' rows');
                }
            }
            setLocalPageInfo();
            rowsCollection.on('add remove reset filter', function() {
                console.log('change');
                setLocalPageInfo();
            });
        }
        if(config.dataMode != 'local') {
            $('ul.pagination.pagesize a').click(function(ev){
                $('ul.pagination.pagesize li').removeClass('active');
                BackboneGrid.currentState.ps = parseInt($(this).html());
                BackboneGrid.currentState.p = 1;
                rowsCollection.fetch({reset: true});
                $(this).parents('li:first').addClass('active');
                ev.preventDefault();

                return false;

            });
        }

        //action logic
        BackboneGrid.MassDeleteButton = 'Div #'+config.id+' button.grid-mass-delete';
        BackboneGrid.AddButton = 'Div #'+config.id+' button.grid-add';
        BackboneGrid.MassEditButton = 'Div #'+config.id+' a.grid-mass-edit';
        BackboneGrid.NewButton = 'Div #'+config.id+' button.grid-new';
        BackboneGrid.RefreshButton = 'Div #'+config.id+' button.grid-refresh';
        BackboneGrid.ExportButton = 'Div #'+config.id+' button.grid-export';

        //if ($(BackboneGrid.AddButton).length > 0 || $(BackboneGrid.MassEditButton).length > 0) {
            modalForm = new BackboneGrid.Views.ModalForm({collection: columnsCollection});
        //}

        if ($(BackboneGrid.ExportButton).length > 0) {
            $(BackboneGrid.ExportButton).on('click', function(ev) {

                if (typeof(BackboneGrid.data_url) !== '') {
                    window.location.href= rowsCollection.url()+'&export=true';
                }
            });
        }

        if ($(BackboneGrid.RefreshButton).length > 0) {
            $(BackboneGrid.RefreshButton).on('click', function(ev) {
                rowsCollection.fetch({reset:true});
                ev.stopPropagation();
                ev.preventDefault();

                return false;
            });
        }

        if ($(BackboneGrid.NewButton).length > 0) {
            $(BackboneGrid.NewButton).on('click', function(ev){
                if ($(this).hasClass('_modal')){
                    modalForm.modalType = 'addable';
                    modalForm.render();
                    $(BackboneGrid.modalShowBtnId).trigger('click');
                } else {
                    var newRow = new BackboneGrid.Models.Row({id: guid(), _new: true});
                    rowsCollection.add(newRow);
                    //gridView.render();
                }
            });
        }

        if ($(BackboneGrid.MassEditButton).length > 0) {
            $(BackboneGrid.MassEditButton).on('click', function(ev){
                modalForm.modalType = 'mass-editable';
                modalForm.render();
                $(BackboneGrid.modalShowBtnId).trigger('click');
            });
        }

        if ($(BackboneGrid.MassDeleteButton).length > 0) {
            $(BackboneGrid.MassDeleteButton).on('click', function(){
                var confirm;
                if ($(this).hasClass('noconfirm'))
                    confirm = true;
                else
                    confirm = window.confirm("Do you really want to delete selected rows?");

                if (confirm) {
                    if (typeof(BackboneGrid.edit_url) !== 'undefined' && BackboneGrid.edit_url.length > 0) {
                        var ids = selectedRows.pluck('id').join(",");
                        $.post(BackboneGrid.edit_url, {id: ids, oper: 'mass-delete'})
                        .done(function(data) {
                            $.bootstrapGrowl("Successfully deleted.", { type:'success', align:'center', width:'auto' });
                            if (BackboneGrid.data_mode !== 'local')
                                rowsCollection.fetch({reset: true});
                            gridView.render();
                        });
                    }

                    if (typeof(g_vent) !== 'undefined' && _.indexOf(BackboneGrid.events, "mass-delete") !== -1) {
                        var rows = selectedRows.toJSON();
                        var ev = {grid: BackboneGrid.id, rows: rows};
                        g_vent.trigger('mass-delete', ev);
                    }
                    rowsCollection.remove(selectedRows.models, {silent:true});
                    selectedRows.reset();
                    $('select.'+config.id+'.js-sel').val('');
                    gridView.render();

                }
            });
        }

        if ($(BackboneGrid.AddButton).length > 0) {
            $(BackboneGrid.AddButton).on('click', function(ev){

                if (typeof(g_vent) !== 'undefined' && _.indexOf(BackboneGrid.events, "add") !== -1) {
                    var rows = selectedRows.toJSON();
                    var evt = {grid: BackboneGrid.id, rows: rows};
                    g_vent.trigger('add', evt);
                } else {

                }

                ev.preventDefault();
                ev.stopPropagation();

                return false;
            });
        }

        //validation
        /*if (BackboneGrid.validation === true) {
            gridView.form = gridView.$el.parents('form:first');

            gridView.form.submit(function(ev) {
                ev.preventDefault();
                ev.stopPropagation();
                if(!gridView.form.valid()) {


                    return false;
                }

                return true;
            });
        }*/


        //quick search
        var quickInputId = '#'+config.id+'-quick-search';

        $(quickInputId).keypress(function(ev){
                var k=ev.keyCode || ev.which;
                if (k == 13) {
                    ev.preventDefault();
                    ev.stopPropagation();

                    if (BackboneGrid.data_mode !== 'local') {
                        BackboneGrid.current_filters['_quick'] = $(ev.target).val();
                        rowsCollection.fetch({reset: true});
                    }
                    return false;
                }
                return true;
        });
        var restricts = ['FCom/PushServer/index.php', 'media/grid/upload', 'my_account/personalize'];
        //ajax loading...
        $( document ).ajaxSend(function(event, jqxhr, settings) {
            var url = settings.url;
            for(var i in restricts) {
                if (url.indexOf(restricts[i]) !== -1)
                    return;
            }
            NProgress.start();
        });
        $( document ).ajaxComplete(function(event, jqxhr, settings) {
            var url = settings.url;
            for(var i in restricts) {
                if (url.indexOf(restricts[i]) !== -1)
                    return;
            }
            NProgress.done();
        });
        NProgress.done();

        if (typeof(g_vent) !== 'undefined' && _.indexOf(BackboneGrid.events, "init") !== -1) {
            var ev= {grid: config.id, ids: rowsCollection.pluck('id')};
            g_vent.trigger('init', ev);
        }
        //console.log(BackboneGrid.events);
        if (typeof(g_vent) !== 'undefined' && _.indexOf(BackboneGrid.events, "init-detail") !== -1) {
            var ev= {grid: config.id, rows: rowsCollection.toJSON()};
            g_vent.trigger('init-detail', ev);
        }


        if (typeof(g_vent) !== 'undefined') {
            //console.log('fetch_rows');
            g_vent.bind('fetch_rows', function(ev) {
                if(ev.grid === config.id) {
                    if(typeof(ev.url) !== 'undefined') {
                        var prevUrl = rowsCollection.url;
                        rowsCollection.url = ev.url;
                        rowsCollection.fetch({
                                                reset:true,
                                                success: function() {
                                                    rowsCollection.url = prevUrl;
                                                    if (typeof(ev.callback) !== 'undefined') {
                                                        ev.callback();
                                                    }
                                                }
                                            });
                    } else {
                        rowsCollection.fetch({reset:true});
                    }

                }
            });

            g_vent.bind('get_rows', function (ev) {
                if(ev.grid === config.id) {
                    ev.callback(rowsCollection.toJSON());
                }
            });

            g_vent.bind('get_cols_collection', function (ev) {
                if(ev.grid === config.id) {
                    ev.callback(columnsCollection);
                }
            });

            g_vent.bind('get_rows_collection', function(ev) {
                if(ev.grid === config.id) {
                    ev.callback(rowsCollection);
                }
            });

            g_vent.bind('get_selected_rows_collection', function(ev) {
                if(ev.grid === config.id) {
                    ev.callback(selectedRows);
                }
            });
        }
    }
});
