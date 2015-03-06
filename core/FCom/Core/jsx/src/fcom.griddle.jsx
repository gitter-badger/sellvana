/** @jsx React.DOM */

define(['underscore', 'react', 'jquery', 'griddle.fcomGridBody', 'griddle.fcomGridFilter', 'fcom.components', 'griddle.custom', 'backbone', 'bootstrap'],
function (_, React, $, FComGridBody, FComFilter, Components, Griddle, Backbone) {

    var dataUrl,
        gridId,
        buildGridDataUrl = function (filterString, sortColumn, sortAscending, page, pageSize) {
            var beginQueryChar = (dataUrl.indexOf('?') != -1) ? '&' : '?';
            return dataUrl + beginQueryChar+ 'gridId=' + gridId + '&p=' + (page + 1) + '&ps=' + pageSize + '&s=' + sortColumn + '&sd=' + sortAscending + '&filters=' + (filterString ? filterString : '{}');
        };

    var FComGriddleComponent = React.createClass({
        getDefaultProps: function () {
            return {
                "config": {},
                "tableClassName": 'fcom-htmlgrid__grid data-table-column-filter table table-bordered table-striped dataTable',
                "callbacks": {}
            }
        },
        componentWillMount: function () {
            this.initColumn();
            //todo: need change way to get right info
            dataUrl = this.props.config.data_url;
            gridId = this.props.config.id;
        },
        initColumn: function () { //todo: almost useless, need to re-check this function
            var columnsConfig = this.props.config.columns;

            var all = _.pluck(columnsConfig, 'name');
            var hide = _.pluck(_.filter(columnsConfig, function(column) { return column.hidden == 'true' || column.hidden == true }), 'name');
            var show = _.difference(all, hide);

            this.props.columns = {all: all, show: show, hide: hide};
            //console.log('this.props.columns', this.props.columns);
            this.props.columnMetadata = columnsConfig;
        },
        getColumn: function (type) {
            switch (type) {
                case 'hide':
                    return this.props.columns.hide;
                    break;
                case 'show':
                    return this.props.columns.show;
                    break;
                default:
                    return this.props.columns.all;
                    break;
            }
        },
        /*callCallbackFunctions: function (type) {
            var grid = this.refs[this.props.config.id];
            //todo: add code to support multi callbacks

            if (typeof this.props.callbacks[type] !== 'undefined') {
                console.log('grid.callback: ', this.props.callbacks[type]);
                if (typeof this.props.callbacks[type] === 'function') {
                    return this.props.callbacks[type](grid);
                } else {
                    var funcName = this.props.callbacks[type];
                    if (typeof window[funcName] === 'function') {
                        return window[funcName](grid);
                    }
                }
            }
        },
        componentDidMount: function () {
            this.callCallbackFunctions('componentDidMount');
        },
        componentDidUpdate: function () {
            this.callCallbackFunctions('componentDidUpdate');
        },*/
        render: function () {
            console.log('config', this.props.config);
            var config = this.props.config;

            //prepare props base on data mode
            var props, state;
            if (config.data_mode == 'local') {
                props = {
                    getExternalResults: null,
                    results: config.data.data
                };
                state = config.state;
            } else {
                props = {
                    getExternalResults: FComDataMethod,
                    results: []
                };
                state = config.data.state;
            }

            //set initial page, use for personalization
            var initPage = state.p - 1;
            if (isNaN(initPage) || initPage < 0) {
                initPage = 0;
            }

            return (
                <div className="fcom-htmlgrid responsive-table">
                    <Griddle showTableHeading={false} tableClassName={this.props.tableClassName} ref={config.id}
                        config={config} initColumns={this.getColumn()}
                        sortColumn={state.s} sortAscending={state.sd == 'asc'}
                        columns={this.getColumn('show')} columnMetadata={this.props.columnMetadata}
                        useCustomGrid={true} customGrid={FComGridBody}
                        resultsPerPage={state.ps}
                        useCustomPager="true" customPager={FComPager} initPage={initPage}
                        showSettings={true} useCustomSettings={true} customSettings={FComSettings}
                        showFilter={true} useCustomFilter="true" customFilter={FComFilter} filterPlaceholderText={"Quick Search"}
                        {...props}
                    />
                </div>
            );
        }
    });

    /**
     * callback to get data from external results
     * @param filterString
     * @param sortColumn
     * @param sortAscending
     * @param page
     * @param pageSize
     * @param callback
     * @constructor
     */
    var FComDataMethod = function (filterString, sortColumn, sortAscending, page, pageSize, callback) {
        $.ajax({
            url: buildGridDataUrl(filterString, sortColumn, sortAscending, page, pageSize),
            dataType: 'json',
            type: 'GET',
            data: {},
            success: function (response) {
                var data = {
                    results: response[1],
                    totalResults: response[0].c
                };

                callback(data);
            },
            error: function (xhr, status, err) {
                //console.error(this.props.url, status, err.toString());
            }
        });
    };

    /**
     * FCom Pager component
     */
    var FComPager = React.createClass({
        getDefaultProps: function () {
            return {
                "maxPage": 0,
                "nextText": "",
                "previousText": "",
                "currentPage": 0,
                "getHeaderSelection": null,
                "totalResults": 0
            }
        },
        pageChange: function (event) {
            event.preventDefault();
            this.props.setPage(parseInt(event.target.getAttribute("data-value")));
        },
        pageFirst: function (event) {
            event.preventDefault();
            this.props.setPage(parseInt(0));
        },
        pageNext: function (event) {
            event.preventDefault();
            this.props.next();
        },
        pagePrevious: function (event) {
            event.preventDefault();
            this.props.previous();
        },
        pageLast: function (event) {
            event.preventDefault();
            this.props.setPage(parseInt(this.props.maxPage) - 1);
        },
        setPageSize: function (event) {
            event.preventDefault();
            var value = event.target.dataset.value;
            //pageSize = parseInt(value);

            this.props.setPageSize(parseInt(value));
            this.props.setPage(0);
        },
        render: function () {
            var headerSelection = this.props.getHeaderSelection();
            if (headerSelection == 'show_selected') {
                return false;
            }
            var pageSizeOptions = this.props.getConfig('page_size_options');
            var pageSize = this.props.resultsPerPage;

            var disabledClass = !this.props.totalResults ? ' disabled' : '';

            var first = <li className={'first' + disabledClass}>
                <a href="#" className="js-change-url" onClick={this.pageFirst}>«</a>
            </li>;
            var previous = <li className={'prev' + disabledClass}>
                <a href="#" className="js-change-url" onClick={this.pagePrevious}>‹</a>
            </li>;
            var next = <li className={'next' + disabledClass}>
                <a className="js-change-url" href="#" onClick={this.pageNext}>›</a>
            </li>;
            var last = <li className={'last' + disabledClass}>
                <a className="js-change-url" href="#" onClick={this.pageLast}>{this.props.maxPage} »</a>
            </li>;

            var options = [];

            var startIndex = Math.max(this.props.currentPage - 5, 0);
            var endIndex = Math.min(startIndex + 11, this.props.maxPage);
            if (this.props.maxPage >= 11 && (endIndex - startIndex) <= 10) {
                startIndex = endIndex - 11;
            }

            for (var i = startIndex; i < endIndex; i++) {
                var selected = this.props.currentPage == i ? "page active" : "page";
                options.push(
                    <li className={selected}>
                        <a href="#" data-value={i} onClick={this.pageChange} className="js-change-url">{i + 1}</a>
                    </li>
                );
            }

            var pageSizeHtml = [];
            for (var j = 0; j < pageSizeOptions.length; j++) {
                selected = (pageSizeOptions[j] == pageSize ? "active" : "") + disabledClass;
                pageSizeHtml.push(
                    <li className={selected}>
                        <a href="#" data-value={pageSizeOptions[j]} onClick={this.setPageSize} className="js-change-url page-size">{pageSizeOptions[j]}</a>
                    </li>
                );
            }

            return (
                <div className="col-sm-6 text-right pagination" style={{ margin: "0" }}>
                    <span className="f-grid-pagination">{this.props.totalResults ? this.props.totalResults + ' record(s)' : 'No data found'}</span>
                    <ul className="pagination pagination-sm pagination-griddle pagesize">
                        {pageSizeHtml}
                    </ul>
                    <ul className="pagination pagination-sm pagination-griddle page">
                        {first}
                        {previous}
                        {options}
                        {next}
                        {last}
                    </ul>
                </div>
            )
        }
    });

    /**
     * FCom Settings component
     */
    var FComSettings = React.createClass({
        mixins: [FCom.Mixin],
        getDefaultProps: function() {
            return {
                "className": "",
                "getConfig": null,
                "selectedColumns": [],
                "refresh": null
            }
        },
        modalSaveMassChanges: function(modal) {
            //todo: combine this with FComGridBody::modalSaveChange()
            var that = this;
            var url = this.props.getConfig('edit_url');
            if (url) {
                var ids = _.pluck(this.props.getSelectedRows(), 'id').join(',');
                var hash = { oper: 'mass-edit', id: ids };
                var form = $(modal.getDOMNode()).find('form');
                form.find('textarea, input, select').each(function() {
                    var key = $(this).attr('id');
                    var val = $(this).val();
                    hash[key] = that.html2text(val);
                });
                form.validate();
                if (form.valid()) {
                    $.post(url, hash, function(data) {
                        if (data) {
                            that.props.refresh();
                            modal.close();
                        } else {
                            alert('error when save');
                            return false;
                        }
                    });
                } else {
                    //error
                    console.log('error');
                    return false;
                }
            }
        },
        doMassAction: function(event) { //top mass action
            var that = this;
            var action = event.target.dataset.action;
            var dataUrl = this.props.getConfig('data_url');
            var editUrl = this.props.getConfig('edit_url');
            var gridId = this.props.getConfig('id');
            var pageSize = this.props.resultsPerPage;

            switch (action) {
                case 'mass-delete':
                    var confirm = false;
                    if ($(event.target).hasClass('noconfirm')) {
                        confirm = true;
                    } else {
                        confirm = window.confirm("Do you really want to delete selected rows?");
                    }

                    if (confirm) {
                        var ids = _.pluck(this.props.getSelectedRows(), 'id').join(',');
                        $.post(dataUrl, { oper: action, id: ids }, function() {
                            that.props.refresh();
                        });
                    }

                    break;
                case 'mass-edit': //mass-edit with modal
                    var modalEleContainer = document.getElementById(gridId + '-modal');
                    React.unmountComponentAtNode(modalEleContainer); //un-mount current modal
                    React.render(
                        <Components.Modal show={true} title="Mass Edit Form" confirm="Save changes" cancel="Close" onConfirm={this.modalSaveMassChanges}>
                            <FComModalMassEditForm editUrl={editUrl} columnMetadata={this.props.columnMetadata} id={gridId} />
                        </Components.Modal>,
                        modalEleContainer
                    );
                    break;
                case 'export':
                    var griddleState = this.props.getGriddleState();
                    var exportUrl = buildGridDataUrl(griddleState.filter, griddleState.sortColumn, griddleState.sortAscending, griddleState.page, pageSize);
                    window.location.href = exportUrl + '&export=true';
                    break;
                default:
                    console.log('do-mass-action');
                    break;
            }

        },
        toggleColumn: function(event) {
            var personalizeUrl = this.props.getConfig('personalize_url');
            var id = this.props.getConfig('id');

            var initColumns = this.props.getInitColumns();
            var selectedColumns = this.props.selectedColumns();
            if(event.target.checked == true && _.contains(selectedColumns, event.target.dataset.name) == false){
                selectedColumns.push(event.target.dataset.name);
                var diff = _.difference(initColumns, selectedColumns);
                if (diff.length > 0) {
                    selectedColumns = initColumns;
                    for(var i=0; i < diff.length; i++) {
                        selectedColumns = _.without(selectedColumns, diff[i]);
                    }
                    this.props.setColumns(selectedColumns);
                } else {
                    this.props.setColumns(initColumns);
                }
            } else {
                /* redraw with the selected initColumns minus the one just unchecked */
                this.props.setColumns(_.without(selectedColumns, event.target.dataset.name));
            }

            if (personalizeUrl) {
                $.post(personalizeUrl, { 'do': 'grid.col.hidden', 'grid': id, 'col': event.target.dataset.name, hidden: !(event.target.checked == true) });
            }

            //don't close dropdown after toggle column
            $(event.target).parents('div.dropdown').addClass('open');
        },
        quickSearch: function(event) {
            this.props.searchWithinResults(event.target.value);
        },
        sortColumns: function(newPosColumns) {
            var personalizeUrl = this.props.getConfig('personalize_url');

            if (personalizeUrl) {
                var id = this.props.getConfig('id');
                var selectedColumns = this.props.selectedColumns();
                var postColumns = [];

                _.forEach(newPosColumns, function(col, index) {
                    postColumns.push({
                        name: col,
                        position: index + 1, //plus 1 because pos 0 always is header-dropdown-selection
                        hidden: !_.contains(selectedColumns, col)
                    })
                });

                $.post(personalizeUrl, { 'do': 'grid.col.orders', 'grid': id, 'cols': JSON.stringify(postColumns) });
            }

            newPosColumns.unshift(0); //add first column again
            this.props.updateInitColumns(newPosColumns);
        },
        componentDidMount: function() {
            var that = this;
            var dom = $(this.getDOMNode()).find('.dd-list');
            dom.sortable({
                handle: '.dd-handle',
                revert: true,
                axis: 'y',
                stop: function () {
                    var newPosColumns = dom.sortable('toArray', {attribute: 'data-id'}); //new position columns array
                    dom.sortable("cancel");
                    that.sortColumns(newPosColumns);
                }
            });
        },
        handleCustom: function(callback, event) {
            if (typeof window[callback] === 'function') {
                console.log('actions.callback: ' + callback);
                return window[callback](this.props.getCurrentGrid());
            }
        },
        render: function () {
            var that = this;
            var id = this.props.getConfig('id');

            //quick search
            var quickSearch = <input type="text" className="f-grid-quick-search form-control" placeholder="Search within results" id={id + '-quick-search'} onChange={this.quickSearch} />;

            var disabledClass = !this.props.getSelectedRows().length ? ' disabled' : '';
            var configActions = this.props.getConfig('actions');
            var buttonActions = [];
            if (configActions) {
                _.forEach(configActions, function(action, name) {
                    var node = '';
                    switch (name) {
                        case 'refresh':
                            node = <a href="#" className={action.class}>{action.caption}</a>;
                            break;
                        case 'export':
                            node = <button className={action.class} data-action='export' onClick={that.doMassAction}>{action.caption}</button>;
                            break;
                        case 'link_to_page':
                            node = <a href="#" className={action.class}>{action.caption}</a>;
                            break;
                        case 'edit':
                            node = <a href='#' className={action.class + disabledClass} data-action="mass-edit" onClick={that.doMassAction} role="button">{action.caption}</a>;
                            break;
                        case 'delete':
                            node = <button className={action.class + disabledClass} type="button" data-action="mass-delete" onClick={that.doMassAction}>{action.caption}</button>;
                            break;
                        case 'add':
                            node = <button className={action.class} type="button">{action.caption}</button>;
                            break;
                        case 'new':
                            node = <button className={action.class} type="button">{action.caption}</button>;
                            break;
                        default:
                            if (action.type) {
                                switch (action.type) {
                                    case 'button':
                                    default:
                                        //compatibility with old backbone grid
                                        node = <button className={action.class + (action.isMassAction ? disabledClass : '')} id={action.id}
                                            type="button" onClick={that.handleCustom.bind(this, action.callback)}>{action.caption}</button>;
                                        break;
                                }
                            } else if (action.html) {
                                node = <span dangerouslySetInnerHTML={{__html: action.html}}></span>;
                            }

                            break;
                    }

                    buttonActions.push(node);
                });
            }

            var options = _.map(this.props.getInitColumns(), function(column) {
                if (column == '0') {
                    return false;
                }

                var checked = _.contains(that.props.selectedColumns(), column);
                var colInfo = _.findWhere(that.props.columnMetadata, {name: column});
                return (
                    <li data-id={column} id={column} className="dd-item dd3-item">
                        <div className="icon-ellipsis-vertical dd-handle dd3-handle"></div>
                        <div className="dd3-content">
                            <label>
                                <input type="checkbox" defaultChecked={checked} data-id={column} data-name={column} className="showhide_column" onChange={that.toggleColumn} />
                                {colInfo ?  colInfo.label : column}
                            </label>
                        </div>
                    </li>
                )
            });

            var styleColumnSettings = {position: 'absolute', top: 'auto', marginTop: '-2px', padding: '0', display: 'block', left: 0};
            return (
                <div className="col-sm-6">
                    {quickSearch}
                    <div className="dropdown dd dd-nestable columns-span" style={{ display: 'inline' }}>
                        <a href="#" className="btn dropdown-toggle showhide_columns" data-toggle="dropdown">
                            Columns <b className="caret"></b>
                        </a>
                        <div id="column-settings" style={styleColumnSettings}>
                            <ol className="dd-list dropdown-menu columns ui-sortable" style={{minWidth: '200px'}}>
                                {options}
                            </ol>
                        </div>
                    </div>
                    {buttonActions}
                </div>
            )
        }
    });

    /**
     * FCom Modal Mass Edit Form
     */
    var FComModalMassEditForm = React.createClass({
        getInitialState: function() {
            var fields = [];
            var shownFields = [];
            _.forEach(this.props.columnMetadata, function(column) {
                if (column.multirow_edit) {
                    fields.push(column);
                }
            });
            /*if (fields.length == 1) {
                shownFields.push(fields[0].name);
            }*/
            return {
                'shownFields': shownFields,
                'fields': fields
            }
        },
        getDefaultProps: function() {
            return {
                'columnMetadata': [],
                'editUrl': '',
                'id': 'modal-mass-form'
            };
        },
        componentDidMount: function() {
            var that = this;
            var domNode = this.getDOMNode();
            $(domNode).find('.well select').select2({
                placeholder: "Select a Field",
                allowClear: true
            });
            $(domNode).find('.well select').on('change', function(e) {
                that.addField(e);
                $(this).select2('data', null);
            });
        },
        addField: function(event) { //render field is selected in dropdown
            if (event.target.value != '') {
                var shownFields = this.state.shownFields;
                shownFields.push(event.target.value);
                this.setState({shownFields: shownFields});
            }
        },
        removeField: function(event) {
            var fieldName = event.target.dataset.field;
            console.log('removeField.field', fieldName);
            console.log('removeField.dataset', event.target.dataset);
            if (fieldName && _.contains(this.state.shownFields, fieldName)) {
                var shownFields = _.without(this.state.shownFields, fieldName);
                this.setState({shownFields: shownFields});
            }
        },
        render: function() {
            console.log('state.fields', this.state.fields);
            console.log('state.shownFields', this.state.shownFields);
            //todo: we have 2 types of render mass-edit, refer https://fulleron.atlassian.net/browse/SC-306

            if (!this.props.editUrl) return null;
            var that = this;
            var gridId = this.props.id;

            var fieldDropDownNodes = this.state.fields.map(function(column) {
                if (!_.contains(that.state.shownFields, column.name)) {
                    return <option value={column.name}>{column.label}</option>;
                }
                return null;
            });
            fieldDropDownNodes.unshift(<option value=""></option>);

            var formElements = this.state.shownFields.map(function(fieldName) {
                var column = _.findWhere(that.state.fields, {name: fieldName});
                return <Components.ModalElement column={column} removeFieldDisplay={true} removeFieldHandle={that.removeField} />
            });

            return (
                <div>
                    <div className="well">
                        <div className="row">
                            <div className="col-sm-12">
                                <select className="select2 form-control" id={gridId + '-form-select'} style={{width: '150px'}}>
                                    {fieldDropDownNodes}
                                </select>
                            </div>
                        </div>
                    </div>
                    <form className="form form-horizontal validate-form" id={gridId + '-modal-mass-form'}>
                        {formElements}
                    </form>
                </div>
            );
        }
    });

    return FComGriddleComponent;
});