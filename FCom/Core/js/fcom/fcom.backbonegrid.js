define(['backbone', 'underscore', 'jquery', 'ngprogress', 'nestable', 'select2', 'jquery.quicksearch'], function(Backbone, _, $, NProgress) {

FCom.BackboneGrid = function(config) {
    var rowsCollection;
    var columnsCollection;
    var gridView;
    var headerView;
    var filterView;
    var colsInfo;
    var selectedRows;
    var settings;
    var BackboneGrid = {
        Models: {},
        Collections: {},
        Views: {},
        currentState: {},
        colsInfo: {},
        data_mode: 'server'

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
        }
    });

    BackboneGrid.Views.ThView = Backbone.View.extend({
        tagName: 'th',
        className: function() {
            var cssClass = this.model.get('cssClass');
            if (this.model.get('sortState').length >0) {
                cssClass += (' sorting_' + this.model.get('sortState'));
            }
            return cssClass;
        },
        attributes: function() {
            var hash = {};
            hash['data-id'] = this.model.get('name');
            if(this.model.has('width'))
                hash['style'] = "width: " + this.model.get('width') + 'px;';
            if(this.model.has('overflow'))
                hash['style'] += hash['style'] +'overflow:hidden;'
            return hash;
        },
        events: {
            'click a': '_changesortState',
            'change select.js-sel': '_checkAction'
        },
        initialize: function() {
           // this.model.on('change', this.render, this);
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
            rowsCollection.each(function(model) {
                if (model.get('editable')) {
                    if (flag) {
                        selectedRows.add(model);
                    } else {
                            selectedRows.remove(model,{silent: true});
                            selectedRows.trigger('remove');
                    }
                    model.set('selected', flag);
                    model.trigger('render');
                }
            });
        },
        _checkAction: function(ev) {

            if ($(ev.target).val().indexOf('upd')!==-1)
                this._selectAction();
            else
                this._showAction();
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
                            $('.fcom-htmlgrid__toolbar.'+BackboneGrid.id+' div.pagination ul').css('display','block');
                            rowsCollection.fetch({reset:true});
                        } else {
                            rowsCollection.filter();
                        }

                    }
                    break;
                case 'show_sel':
                    if(!BackboneGrid.showingSelected) {
                        console.log('show_sel!');
                        BackboneGrid.prev_data_mode = BackboneGrid.data_mode;
                        BackboneGrid.prev_originalRows = rowsCollection.originalRows;
                        BackboneGrid.showingSelected = true;
                        if(BackboneGrid.data_mode !== 'local')
                            $('.fcom-htmlgrid__toolbar.'+BackboneGrid.id+' div.pagination ul').css('display','none');

                        BackboneGrid.data_mode = 'local';
                        rowsCollection.originalRows = selectedRows;
                        rowsCollection.reset(selectedRows.models);
                        console.log(selectedRows);
                    }
                    break;
            }

        },
        _clearSelection: function() {
            selectedRows.reset();
            rowsCollection.each(function(model) {
                if(model.get('selected'))
                    model.set('selected', false);
                model.trigger('render');
            });
        },
        //function to select or unselect all rows of page and empty selected rows
        _selectAction: function() {
            var key = this.$el.find('select.js-sel').val();
            console.log(key);
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
        },
        render: function() {
            console.log('headerver_render');
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
            colsInfo: [],
            selected: false,
            editable: true
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
                var ev = {grid: BackboneGrid.id, id: id};
                g_vent.trigger('delete', ev);
            } else {
                var hash = {};
                hash.id = id;
                hash.oper = 'del';
                $.post(BackboneGrid.edit_url, hash);
            }

            return false;
        },
        save: function() {
            var id = this.get('id');
            var hash = this.changedAttributes();
            hash.id = id;
            hash.oper = 'edit';
            if (BackboneGrid.data_mode === 'local') {
                /*var funcName = BackboneGrid.callBacks['edit'];
                var command = funcName+'(this.toJSON());';
                eval(command);*/
            } else {
                $.post(BackboneGrid.edit_url, hash);
            }

            if (typeof(g_vent) !== 'undefined' && _.indexOf(BackboneGrid.events, "edit") !== -1) {
                var row = this.toJSON();
                console.log(row);
                var ev = {grid: BackboneGrid.id, row: row};
                g_vent.trigger('edit', ev);
            }

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

                if (typeof(g_vent) !== 'undefined') {
                    g_vent.bind('silent_inject', this._silentInjectRows);
                }
            }
        },
        _silentInjectRows: function(ev) {
            if (ev.grid !== BackboneGrid.id)
                return;

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
                                            flag = flag && (last_index + filterVal.length) === modelVal.length;
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
                console.log(temp.models.length);
                this.reset(temp.models);
                gridView.render();
        },
        addInOriginal: function(model){
            this.originalRows.add(model);
            console.log('add');
        },
        removeInOriginal: function(model){
            this.originalRows.remove(model);
            console.log('remove');
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
                append += (keys[i] + '=' + BackboneGrid.currentState[keys[i]]);
            }
            append += ('&filters=' + JSON.stringify(BackboneGrid.current_filters));
            return this.data_url + '?' + append + '&gridId='+BackboneGrid.id;
        },
        parse: function(response) {
            if (response[0].c) {
                var mp = Math.ceil(response[0].c / BackboneGrid.currentState.ps) ;
                /*console.log('c=', response[0].c);
                console.log('ps=', BackboneGrid.currentState.ps);
                console.log('mp=', mp);
                console.log('bb.mp=', BackboneGrid.currentState.mp);*/
                if (mp !== BackboneGrid.currentState.mp) {
                    BackboneGrid.currentState.mp = mp;
                    BackboneGrid.currentState.c = response[0].c;
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
            //'keydown .form-control': '_validate',
            'click a.btn-delete': '_deleteRow'
        },
        initialize: function() {
            this.model.on('render',this.render, this);
            this.model.on('remove', this._destorySelf, this);
            //this.model.on('change', this.render, this);
        },
        _validate: function(ev) {
            var val = $(ev.target).val();
            var name = $(ev.target).attr('data-col');
            var col = columnsCollection.findWhere({name: name});
            if (typeof(col) != 'undefined') {
                if (typeof(col.get('validate')) !== 'undefined') {
                    switch(col.get('validate')) {
                        case 'number':
                            $(ev.target).toggleClass('unvalid', isNaN(val));
                            return  !isNaN(val);
                        break;
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
                    gridView.render();
                }
            }


        },
        _cellValChanged: function(ev) {

            var val = $(ev.target).val();
            var name = $(ev.target).attr('data-col');
            if(!this._validate(ev))
            {
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
                return;
            }
            this.model.set(name, val);
            this.model.save();

        },
        _deleteRow: function(ev) {
            var confirm = window.confirm("Do you want to really delete?");
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
            var colsInfo = columnsCollection.toJSON();
            this.$el.html(this.template({row:this.model.toJSON(), colsInfo: colsInfo}));
            return this;
        }
    });

    BackboneGrid.Views.GridView = Backbone.View.extend({
      //  el: 'table tbody',
        initialize: function () {
            this.collection.on('reset', this.render, this);
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
            return $('#' + BackboneGrid.id);
        },
        render: function() {
            console.log('gridview-render');
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

            var name = 'hidden' + this.model.get('name');
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
            this.setElement('#' + BackboneGrid.id + ' .dd-list');
        },
        orderChanged: function(ev) {
            console.log('orderChanged');
            var orderJson = $('.dd').nestable('serialize');
            var changedFlag = false;
            for(var i in orderJson) {
                var key = orderJson[i].id;
                colModel = columnsCollection.findWhere({name: key});
                if (parseInt(colModel.get('position')) !== parseInt(i) + columnsCollection.append) {
                    colModel.set('position', parseInt(i) + columnsCollection.append);
                    changedFlag = true;
                }

            }

            if (!changedFlag)
                return;

            columnsCollection.sort();
            gridView.render();

             $.post(BackboneGrid.personalize_url,{
                'do': 'grid.col.orders',
                'cols': colsInfo,
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
            $('.dd').nestable().on('change',this.orderChanged);
        },
        addLiTag: function(model) {
            if(model.get('label') !== '') {
                var checkView = new BackboneGrid.Views.ColCheckView({model:model});
                this.$el.append(checkView.render().el);
            }
        }
    });
    BackboneGrid.Views.FilterCell = Backbone.View.extend({
        className: 'btn-group dropdown',
        attributes: {
            style: 'margin-right:20px;'
        },

        _filter: function(val) {
            if (val === false) {
                this.model.set('filterVal','');
                this.render();
                if(typeof(BackboneGrid.current_filters[this.model.get('name')]) === 'undefined')
                    return;
                delete BackboneGrid.current_filters[this.model.get('name')];

            } else {

                if (val.length === 0)
                    delete BackboneGrid.current_filters[this.model.get('name')];
            }

            if (BackboneGrid.data_mode === 'local') {
                rowsCollection.filter();
            } else {
                rowsCollection.fetch({reset:true});
            }
        },
        preventDefault: function(ev) {
                ev.stopPropagation();
                return false;
        },
        render: function() {
            this.$el.html(this.template(this.model.toJSON()));
            return this;
        }
    });
    BackboneGrid.Views.FilterTextCell = BackboneGrid.Views.FilterCell.extend({
        events: {
            'click input': 'preventDefault',
            'click button.update': 'filter',
            //'click .filter-box': 'preventDefault',
            'click .filter-text-sub': 'subButtonClicked',
            'click a.filter_op': 'filterOperatorSelected',
            'keyup': 'filterValChanged',
            'click button.clear': '_closeFilter',
            'keyup input': '_checkEnter'
        },
        _closeFilter: function(ev) {
            this._filter(false);
        },
        _checkEnter: function(ev) {
            var evt = ev || window.event;
            var charCode = evt.keyCode || evt.which;
            if (charCode === 13) {
                this.$el.find('button.update').trigger('click');
            }
        },
        filterValChanged: function(ev) {
            this.model.set('filterVal', this.$el.find('input:first').val());
        },
        filterOperatorSelected: function(ev) {
            this.filterValChanged();
            var operator = $(ev.target);
            this.model.set('filterOp',operator.attr('data-id'));
            this.model.set('filterLabel', operator.html());
            this.$el.find('ul.filter-sub').css('display','none');
            this.render();
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
            this._filter(filterVal);
            this.model.set('filterVal',filterVal);
            this.render();
        },

    });

    BackboneGrid.Views.FilterMultiselectCell = BackboneGrid.Views.FilterCell.extend({
        events: {
        'click button.clear': '_closeFilter',
        'blur input[type="text"]': '_onBlur'
        },
        _onBlur: function(ev) {
            //$('div.select2-drop.select2-drop-multi').css('display','none');
        },
        checkEnter: function(ev) {

        },
        filter: function(val) {
            BackboneGrid.current_filters[this.model.get('name')] = val;
            this._filter(val);
        },
        render: function() {
            this.$el.html(this.template(this.model.toJSON()));
            var options = this.model.get('options');
            var data = [];
            for(var key in options) {
                data[data.length] = {id: key, text: options[key]};
            }

            this.$el.find('#multi_hidden:first').select2({
                multiple: true,
                data: data,
                placeholder: 'All',
                //closeOnSelect: true
            });
            var self = this;
            this.$el.find('#multi_hidden:first').on('change', function() {
                self.filter($(this).val());
            });

            return this;
        }

    });

    BackboneGrid.Views.FilterView = Backbone.View.extend({
        initialize: function() {
            var div = 'div.row.datatables-top.'+BackboneGrid.id + ' div.col-sm-9 span:nth-child(2)';
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
                    case 'multiselect':
                        filterCell = new BackboneGrid.Views.FilterMultiselectCell({model:model});
                        break;
                }
                this.$el.append(filterCell.render().el);
            }
        }
    });

    BackboneGrid.Views.MassEditElement = Backbone.View.extend({
        className: 'form-group',
        events: {
            'change': '_setVal'
        },
        _setVal: function(ev) {
            var key = $(ev.target).attr('id');
            var val = $(ev.target).val();
            BackboneGrid.massEditVals[key] = val;
        },
        render: function() {
            this.$el.html(this.template(this.model.toJSON()));
            return this;
        }
    });
    BackboneGrid.Views.MassEditForm = Backbone.View.extend({
        _saveChanges: function(ev) {
            for( var key in BackboneGrid.massEditVals) {
                if (BackboneGrid.massEditVals[key] === '')
                    delete BackboneGrid.massEditVals[key];
            }

            var ids = selectedRows.pluck('id').join(",");

            if (BackboneGrid.data_mode === 'local') {
                /*var funcName = BackboneGrid.callBacks['mass-edit'];
                var jsons = selectedRows.toJSON();
                for  (var i in jsons) {
                    for(var key in BackboneGrid.massEditVals)
                        jsons[i][key] = BackboneGrid.massEditVals[key];
                }
                var command = funcName + '(jsons);'
                eval(command);*/
            } else {
                var hash = BackboneGrid.massEditVals;
                hash.id = ids;
                hash.oper = 'mass-edit';
                $.post(BackboneGrid.edit_url, hash)
                .done(function(data) {
                    $.bootstrapGrowl("Successfully saved.", { type:'success', align:'center', width:'auto' });
                });
                delete BackboneGrid.massEditVals.id;
                delete BackboneGrid.massEditVals.oper;
            }

            if (typeof(g_vent) !== 'undefined' && _.indexOf(BackboneGrid.events, "mass-edit") !== -1) {
                var rows = selectedRows.toJSON();
                for  (var i in rows) {
                    for(var key in BackboneGrid.massEditVals)
                        rows[i][key] = BackboneGrid.massEditVals[key];
                }
                var evt = {grid: BackboneGrid.id, rows: rows};
                g_vent.trigger('mass-edit', evt);
            }

            selectedRows.each(function(model) {
                for(var key in BackboneGrid.massEditVals) {
                    model.set(key, BackboneGrid.massEditVals[key]);
                    model.trigger('render');
                }
            });
            $(ev.target).parents('div.modal-dialog:first').find('input').val('');
            $(ev.target).parents('div.modal-dialog:first').find('select').val('');
            $(ev.target).prev().trigger('click');
            BackboneGrid.massEditVals = {};

        },
        initialize: function() {
            //this.collection.on('sort change reset', this.render, this);
            this.$el.parents('div.modal-dialog:first').find('button.save').click(this._saveChanges);
        },
        render: function() {
            this.$el.html('');
            BackboneGrid.massEditVals = {};
            this.collection.each(this.addElementDiv, this);
            this.$el.parents('div.modal-dialog:first').find('input').val('');
            this.$el.parents('div.modal-dialog:first').find('select').val('');
        },
        addElementDiv: function(model) {
            if (model.has('editable')) {
                var elementView = new BackboneGrid.Views.MassEditElement({model: model});
                this.$el.append(elementView.render().el);
            }
        }
    });
    function updatePageHtml(p,mp) {

            p = BackboneGrid.currentState.p;
            mp = BackboneGrid.currentState.mp;
            console.log(BackboneGrid.currentState.mp);
            var html = '';

            html += '<li class="first'+ (p<=1 ? ' disabled' : '') + '">';
            html += '<a class="js-change-url" href="#">&laquo;</a>';
            html += '</li>';

            html += '<li class="prev'+ (p<=1 ? ' disabled' : '') + '">';
            html += '<a class="js-change-url" href="#">&lsaquo;</a>';
            html += '</li>';


            for (var i= Math.max(p-3,1); i<=Math.min(p+3,mp);i++) {
                html += '<li class="page' + (i == p ? ' active' : '') + '">';
                html += '<a class="js-change-url" data-page="" href="#">' +  i +'</a>';
                html += '</li>';
            }

            html += '<li class="next'+ (p>=mp ? ' disabled' : '') + '">';
            html += '<a class="js-change-url" href="#">&rsaquo;</a>';
            html += '</li>';

            html += '<li class="last'+ (p>=mp ? ' disabled' : '') + '">';
            html += '<a class="js-change-url" href="#">&raquo;</a>';
            html += '</li>';


            $('ul.' + BackboneGrid.id + '.pagination.page').html(html);

            var caption = 'Page: '+p+' of '+mp+' | '+BackboneGrid.currentState.c+' records';
            $('div.'+BackboneGrid.id+'-pagination').html(caption);
    }

    function guid() {
        function _p8(s) {
            var p = (Math.random().toString(16)+"000000000").substr(2,8);
            return s ? "-" + p.substr(0,4) + "-" + p.substr(4,4) : p ;
        }
        return _p8() + _p8(true) + _p8(true) + _p8();
    }

        NProgress.start();
        //general settings
        _.templateSettings.variable = 'rc';
        BackboneGrid.id = config.id;
        BackboneGrid.personalize_url = config.personalize_url;
        BackboneGrid.edit_url = config.edit_url;
        BackboneGrid.current_filters= {};
        BackboneGrid.quickInputId = '#' + config.id + '-quick-search';
        BackboneGrid.events = config.events;
        //BackboneGrid.callBacks = config.callBacks;
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

        BackboneGrid.Views.ThView.prototype.template = _.template($('#' + config.id + '-header-template').html());
        BackboneGrid.Views.HeaderView.prototype.el = "#" + config.id + " thead tr";
        //tbody
        BackboneGrid.Views.GridView.prototype.el = "table#" + config.id + " tbody";
        BackboneGrid.Views.RowView.prototype.template = _.template($('#' + config.id + '-row-template').html());
        BackboneGrid.Collections.Rows.prototype.data_url = config.data_url;

        //filtering settings
        BackboneGrid.Views.FilterTextCell.prototype.template = _.template($('#' + config.id + '-text-filter-template').html());
        BackboneGrid.Views.FilterMultiselectCell.prototype.template = _.template($('#' + config.id + '-multiselect-filter-template').html());
        //column visiblity checkbox view
        BackboneGrid.Views.ColCheckView.prototype.template = _.template($('#' + config.id + '-col-template').html());

        //mass edit modal view
        BackboneGrid.Views.MassEditForm.prototype.el = "div#" + config.id + " div#mass-edit div.modal-body";
        BackboneGrid.Views.MassEditElement.prototype.template = _.template($('#' + config.id + '-edit-template').html());


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
            if (c.name != 'id') {
                if (c.hidden === 'false')
                    c.hidden = false;
                if (c.name === 0) {
                    columnsCollection.append = 2;
                }

                c.id = config.id + '-' + c.name;
                //c.style = c['width'] ? "width:" + c['width'] + "px" : '';

                c.cssClass = '';
                if (!c['no_reorder'])
                    c.cssClass += 'js-draggable ';

                if (state['s'] && c['name'] && state['s'] == c['name']) {
                    //c.cssClass += 'sort-' + state['sd'] + ' ';
                    c.sortState = state['sd'];
                } else {
                    //c.cssClass += 'sort';
                    c.sortState = "";
                }
                var filter = _.findWhere(filters, {field: c.name});
                if (typeof(filter) !== 'undefined') {
                    c.filtering = true;
                    c.filter_type = filter['type'];

                    if (filter.type === 'text')
                            {
                                c.filterOp = 'contains';
                                c.filterLabel = 'Contains';
                            }
                }

                if (typeof(c.default) !== 'undefined') {
                    BackboneGrid.Models.Row.prototype.defaults[c.name] = c.default;
                } else {
                    BackboneGrid.Models.Row.prototype.defaults[c.name] = '';
                }
                var ColModel = new BackboneGrid.Models.ColModel(c);
                columnsCollection.add(ColModel);
            }
        }

        headerView = new BackboneGrid.Views.HeaderView({collection: columnsCollection});
        headerView.render();
        var colsVisibiltyView = new BackboneGrid.Views.ColsVisibiltyView({collection: columnsCollection});
        colsVisibiltyView.render();
        filterView = new BackboneGrid.Views.FilterView({collection: columnsCollection});
        filterView.render();
        //body view
        var rows = config.data.data;
        rowsCollection = new BackboneGrid.Collections.Rows;

        //showing selected rows count
        selectedRows = new Backbone.Collection;
        multiselectCol = columnsCollection.findWhere({type: 'multiselect'});
        selectedRows.on('add remove reset',function(){
            multiselectCol.set('selectedCount', selectedRows.length);
            multiselectCol.trigger('render');
            if (selectedRows.length > 0) {
                $(BackboneGrid.massDeleteButton).removeClass('disabled');
                $(BackboneGrid.massEditButton).removeClass('disabled');
            } else {
                $(BackboneGrid.massDeleteButton).addClass('disabled');
                $(BackboneGrid.massEditButton).addClass('disabled');
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

        //mass action logic
        BackboneGrid.massDeleteButton = 'Div #' + config.id + ' button.grid-mass-delete';
        BackboneGrid.AddButton = 'Div #' + config.id + ' button.grid-add';
        BackboneGrid.massEditButton = 'Div #' + config.id + ' a.grid-mass-edit';
        BackboneGrid.NewButton = 'Div #' + config.id + ' button.grid-new';
        if ($(BackboneGrid.NewButton).length > 0) {
            $(BackboneGrid.NewButton).on('click', function(ev){
                var newRow = new BackboneGrid.Models.Row({id: guid(), _new: true});
                rowsCollection.add(newRow);
                gridView.render();
            });
        }
        if ($(BackboneGrid.massDeleteButton).length > 0) {
            $(BackboneGrid.massDeleteButton).on('click', function(){
                var confirm = window.confirm("Do you really want to delete selected rows?");
                if (confirm) {
                    if (BackboneGrid.data_mode === 'local' && typeof(g_vent) !== 'undefined' && BackboneGrid.events.indexOf('mass-delete') !== -1) {
                        g_vent.trigger('mass-delete', {grid: BackboneGrid.id, rows: selectedRows.toJSON()});
                        rowsCollection.remove(selectedRows.models, {silent:true});
                        gridView.render();

                    } else {
                        var ids = selectedRows.pluck('id').join(",");
                        $.post(BackboneGrid.edit_url, {id: ids, oper: 'mass-delete'})
                        .done(function(data) {
                            $.bootstrapGrowl("Successfully deleted.", { type:'success', align:'center', width:'auto' });
                            rowsCollection.fetch({reset: true});
                            //gridView.render();
                        });
                    }

                    if (typeof(g_vent) !== 'undefined' && _.indexOf(BackboneGrid.events, "mass-delete") !== -1) {
                        var rows = selectedRows.toJSON();
                        var ev = {grid: BackboneGrid.id, rows: rows};
                        g_vent.trigger('mass-delete', ev);
                    }

                    selectedRows.reset();
                    $('select.' + config.id + '.js-sel').val('');

                }
            });
        }

        if ($(BackboneGrid.massEditButton).length > 0) {
            var massEditForm = new BackboneGrid.Views.MassEditForm({collection: columnsCollection});
            massEditForm.render();
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

        //quick search
        var quickInputId = '#' + config.id + '-quick-search';


        $(quickInputId).on('keyup', function(ev){
                var evt = ev || window.event;
                var charCode = evt.keyCode || evt.which;
                if (BackboneGrid.data_mode !== 'local' && charCode == 13) {

                    BackboneGrid.current_filters['_quick'] = $(ev.target).val();
                    rowsCollection.fetch({reset: true});
                    ev.preventDefault();
                    ev.stopPropagation();
                    return false;
                }
                return true;
        });

        //ajax loading...
        $( document ).ajaxSend(function(event, jqxhr, settings) {
            console.log(settings.url);
            console.log(settings.url.indexOf('FCom/PushServer/index.php'));
            if (settings.url.indexOf('FCom/PushServer/index.php') === -1)
                NProgress.start();
        });
        $( document ).ajaxComplete(function(event, jqxhr, settings) {
            if (settings.url.indexOf('FCom/PushServer/index.php') === -1)
                NProgress.done();
        });
        NProgress.done();

        if (typeof(g_vent) !== 'undefined' && _.indexOf(BackboneGrid.events, "init") !== -1) {
            var ev= {grid: config.id, ids: rowsCollection.pluck('id')};
            g_vent.trigger('init', ev);
        }
    }
});
