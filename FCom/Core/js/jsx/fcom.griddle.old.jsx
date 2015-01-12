/** @jsx React.DOM */

define(['underscore', 'react', 'jquery', 'jsx!griddle.fcomGridBody', 'jsx!griddle', 'backbone', 'bootstrap', 'jsx!fcom.components'],
function (_, React, $, FComGridBody, Griddle, Backbone, Components) {

    /**
     *
     * @param config
     * @constructor
     */
    FCom.Griddle = function (config) {
        console.log('config', config);
        var page_size_options = config.page_size_options;
        var totalResults = config.data.state.c;
        var initColumns;
        var tableClassName = 'fcom-htmlgrid__grid data-table-column-filter table table-bordered table-striped dataTable';

        var FComGriddleComponent = React.createClass({
            getDefaultProps: function () {
                return {
                    "resultsPerPage": config.state.ps
                }
            },
            render: function () {
                initColumns = _.pluck(config.columns, 'name');

                var content = <Griddle showTableHeading={false} tableClassName={tableClassName}
                    columns={initColumns} columnMetadata={config.columns}
                    useCustomGrid={true} customGrid={FComGridBody}
                    getExternalResults={FComDataMethod} resultsPerPage={this.props.resultsPerPage}
                    useCustomPager="true" customPager={FComPager}
                    showSettings={true} useCustomSettings={true} customSettings={FComSettings}
                    //showFilter={true} useCustomFilter="true" customFilter={FComFilter} filterPlaceholderText={"Quick Search"}
                />;

                return (
                    <div>{content}</div>
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
                url: config.data_url + '?gridId=' + config.id + '&p=' + (page + 1) + '&ps=' + pageSize + '&s=' + sortColumn + '&sd=' + sortAscending + '&filters=' + filterString,
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
                    console.error(this.props.url, status, err.toString());
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
                    "currentPage": 0
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
                var value = parseInt(event.target.getAttribute("data-value"));

                document.getElementById(config.id).innerHTML = '';
                config.state.ps = value;

                React.render(
                    <FComGriddleComponent resultsPerPage={config.state.ps} />, document.getElementById(config.id)
                );
            },
            render: function () {
                var first = "";
                var previous = "";
                var next = "";
                var last = "";

                first = <li className="first">
                    <a href="#" className="js-change-url" onClick={this.pageFirst}>«</a>
                </li>;
                previous = <li className="prev">
                    <a href="#" className="js-change-url" onClick={this.pagePrevious}>‹</a>
                </li>;
                next = <li className="next">
                    <a className="js-change-url" href="#" onClick={this.pageNext}>›</a>
                </li>;
                last = <li className="last">
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
                    options.push(<li className={selected}>
                        <a href="#" data-value={i} onClick={this.pageChange} className="js-change-url">{i + 1}</a>
                    </li>);
                }

                var pageSizeHtml = [];
                for (var j = 0; j < page_size_options.length; j++) {
                    var selected = page_size_options[j] == config.state.ps ? "active" : "";
                    pageSizeHtml.push(<li className={selected}>
                        <a href="#" data-value={page_size_options[j]} onClick={this.setPageSize} className="js-change-url page-size">{page_size_options[j]}</a>
                    </li>);
                }

                return (

                    <div className="col-sm-12 text-right">
                        <span className="f-grid-pagination">{totalResults} record(s)</span>

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

        var FComFilter = React.createClass({
            getDefaultProps: function () {
                return {
                    "placeholderText": "Quick Search",
                    "operations": [
                        {
                            "operation": "contains",
                            "display": "contains"
                        },
                        {
                            "operation": "not",
                            "display": "does not contain"
                        },
                        {
                            "operation": "equal",
                            "display": "is equal to"
                        },
                        {
                            "operation": "start",
                            "display": "start with"
                        },
                        {
                            "operation": "end",
                            "display": "end with"
                        }
                    ],
                    "filters": [
                        // Example filters, remove later
                        {
                            "column": "id",
                            "display": "ID",
                            "defaultCheck": "checked",
                            "defaultValue": "",
                            "defaultOperator": ""
                        },
                        {
                            "column": "firstname",
                            "display": "Firstname",
                            "defaultCheck": "checked",
                            "defaultValue": "",
                            "defaultOperator": ""
                        },
                        {
                            "column": "lastname",
                            "display": "Lastname",
                            "defaultCheck": "checked",
                            "defaultValue": "",
                            "defaultOperator": ""
                        },
                        {
                            "column": "email",
                            "display": "Email",
                            "defaultCheck": "checked",
                            "defaultValue": "",
                            "defaultOperator": ""
                        }
                    ]
                }
            },
            handleChange: function (event) {
                this.props.changeFilter(event.target.value);
            },
            toggleDropdown: function (event) {
                event.preventDefault();

                var selected = event.target;
                var parent = $(selected).parent();

                if (!$(parent).hasClass('open')) {
                    if ($('.buttondropdown-backdrop').length == 0) {
                        $(parent).append($('<div class="buttondropdown-backdrop"/>').on('click', this.clearMenus));
                    }
                }

                $(parent).toggleClass('open').trigger('shown.bs.dropdown');
            },
            clearMenus: function () {
                $('.dropdown-toggle').each(function (e) {
                    var parent = $(this).parent();
                    if (!$(parent).hasClass('open')) {
                        return;
                    }
                    $(parent).trigger(e = $.Event('hide.bs.dropdown'));

                    if (e.isDefaultPrevented()) {
                        return;
                    }

                    $(parent).removeClass('open').trigger('hidden.bs.dropdown');
                    $(parent).find('.buttondropdown-backdrop').remove();
                })
            },
            clearFilter: function (event) {
                var target = event.target;

                $(target).parents('.f-grid-filter').removeClass('f-grid-filter-val');
                $(target).parents('.f-grid-filter').find('.filter-text-main .f-grid-filter-value').html('All');
                $(target).parents('.f-grid-filter').removeClass('open').trigger('hidden.bs.dropdown');
                $('.buttondropdown-backdrop').remove();
            },
            updateFilter: function (event) {
                var target = event.target;

                if ($.trim($(target).parents('.f-grid-filter').find('.selected-value').val()) != '') {
                    var caption = $(target).parents('.f-grid-filter').find('.selected-operator').html() + ' "' +
                        $(target).parents('.f-grid-filter').find('.selected-value').val() + '"';
                    $(target).parents('.f-grid-filter').addClass('f-grid-filter-val');
                    $(target).parents('.f-grid-filter').find('.filter-text-main .f-grid-filter-value').html(caption);
                } else {
                    $(target).parents('.f-grid-filter').removeClass('f-grid-filter-val');
                    $(target).parents('.f-grid-filter').find('.filter-text-main .f-grid-filter-value').html('All');
                }
                $(target).parents('.f-grid-filter').removeClass('open').trigger('hidden.bs.dropdown');
                $('.buttondropdown-backdrop').remove();

            },
            toggleFilter: function (event) {
                var target = event.target;
                var dataId = $(target).attr('data-id');

                $('#' + dataId).toggleClass('hide');
            },
            selectOperator: function (event) {
                event.preventDefault();

                var target = event.target;
                $(target).parents('.operator-dropdown').find('.selected-operator').html($(target).text());
                $(target).parents('.operator-dropdown').removeClass('open').trigger('hidden.bs.dropdown');
            },
            render: function () {
                var quickSearch = <input type="text" className="f-grid-quick-search form-control" placeholder={this.props.placeholderText} onChange={this.handleChange} />;

                var filterOperators = [];
                for (var i = 0; i < this.props.operations.length; i++) {
                    var op = this.props.operations[i];
                    filterOperators.push(<li>
                        <a href="#" data-id={op.operation} className="filter_op" onClick={this.selectOperator}>{op.display}</a>
                    </li>);
                }

                var filterOptions = [];
                var filters = [];

                for (var i = 0; i < this.props.filters.length; i++) {
                    var filter = this.props.filters[i];
                    var dataId = 'filter-' + filter.column;

                    // Create checkbox to enable/disable filter
                    filterOptions.push(
                        <li data-id="title" className="dd-item dd3-item">
                            <div className="icon-ellipsis-vertical dd-handle dd3-handle"></div>
                            <div className="dd3-content">
                                <label><input type="checkbox" defaultChecked={filter.defaultCheck} data-id={dataId} className="showhide_column" onChange={this.toggleFilter}/> {filter.display}</label>
                            </div>
                        </li>
                    );

                    // Create filter by column item
                    filters.push(
                        <div className="btn-group f-grid-filter dropdown" id={dataId}>
                            <FCom.Components.Button type="button" className="dropdown-toggle filter-text-main" onClick={this.toggleDropdown}>
                                <span className="f-grid-filter-field">{filter.display}</span>:&nbsp;
                                <span className="f-grid-filter-value">All</span>&nbsp;
                                <span className="caret"></span>
                            </FCom.Components.Button>

                            <ul className="dropdown-menu filter-box">
                                <li>
                                    <div className="input-group">
                                        <div className="input-group-btn operator-dropdown dropdown">
                                            <FCom.Components.Button type="button" className="btn-default dropdown-toggle filter-text-sub" onClick={this.toggleDropdown}>
                                                <span className="selected-operator">
                                                    {filter.defaultOperator != '' ? filter.defaultOperator : this.props.operations[0].display}
                                                </span>&nbsp;
                                                <span className="caret"></span>
                                            </FCom.Components.Button>

                                            <ul className="dropdown-menu filter-sub">{filterOperators}</ul>
                                        </div>

                                        <input type="text" defaultValue={filter.defaultValue} className="form-control selected-value" />
                                        <div className="input-group-btn">
                                            <FCom.Components.Button type="button" className="btn-primary update" onClick={this.updateFilter}>
                                                Update
                                            </FCom.Components.Button>
                                        </div>
                                    </div>
                                </li>
                            </ul>

                            <abbr className="select2-search-choice-close" onClick={this.clearFilter}></abbr>
                        </div>
                    );
                }

                return (
                    <div className="f-grid-top f-grid-toolbar f-grid-filters clearfix">
                        <div className="f-col-filters-selection pull-left">
                        {quickSearch}
                            <span className="dropdown">
                                <button className="btn dropdown-toggle showhide_columns" onClick={this.toggleDropdown}>
                                    Filters&nbsp;<span className="caret"></span>
                                </button>
                                <ul className="dd-list dropdown-menu filters ui-sortable">{filterOptions}</ul>
                            </span>
                        </div>

                        <span className="f-filter-btns">
                            {filters}
                        </span>
                    </div>
                );
            }
        });

        var FComSettings = React.createClass({
            getDefaultProps: function() {
                return {
                    "className": ""
                }
            },
            toggleColumn: function(event) {
                var selectedColumns = this.props.selectedColumns;
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
            },
            render: function () {
                var options = [];
                for (var i = 0; i < initColumns.length; i++) {
                    if (initColumns[i] != "0") {
                        var checked = _.contains(this.props.selectedColumns, initColumns[i]);
                        options.push(
                            <li data-id={initColumns[i]} className="dd-item dd3-item">
                                <div className="icon-ellipsis-vertical dd-handle dd3-handle"></div>
                                <div className="dd3-content">
                                    <label><input type="checkbox" checked={checked} data-id={initColumns[i]} data-name={initColumns[i]} className="showhide_column" onChange={this.toggleColumn}/> {initColumns[i]}</label>
                                </div>
                            </li>
                        );
                    }
                }
                return (
                    <div className="col-sm-12">
                        <span className="dropdown dd dd-nestable columns-span">
                            <a href="#" className="btn dropdown-toggle showhide_columns" data-toggle="dropdown">
                                Columns <b className="caret"></b>
                            </a>
                            <ol className="dd-list dropdown-menu columns ui-sortable">
                                {options}
                            </ol>
                        </span>
                        <a className="btn grid-mass-edit btn-success disabled" role="button" href="#" >Edit</a>
                        <button className="btn grid-mass-delete btn-danger disabled" type="button">Delete</button>
                    </div>
                )
            }
        });

        React.render(
            <FComGriddleComponent resultsPerPage={config.state.ps} />, document.getElementById(config.id)
        );
    };
});