define(['jquery', 'react', 'jsx!fcom.components', 'underscore', 'ckeditor'], function ($, React, Components, _) {
    var cmsBlocks, customerGroups;

    function getCmsBlocks(url, callback) {
        $.get(url).done(callback);
    }

    var CentralPageApp = React.createClass({
        getCmsOptions: function () {
            if (!cmsBlocks) {
                var self = this;
                var url = this.props.base_url + '/' + this.props.cmsBlocksUrl;
                getCmsBlocks(url, function (result) {
                    if (result.items) {
                        cmsBlocks = result.items.map(function (item) {
                            return <option key={item.id} value={item.text}>{item.text}</option>
                        });
                        //cmsBlocks.unshift(<option key="0" value="">Select block handle</option>);
                        self.forceUpdate();
                    }
                });
                //cmsBlocks = [<option key="1">Test</option>];
            }
            return cmsBlocks;
        },
        render: function () {
            var types;
            if (this.props.type === 'cms_block') {
                var cmsOptions = this.getCmsOptions(), value;
                if (this.props.values) {
                    value = this.props.values[this.props.cmsOptions.id];
                }
                types =
                    <div>
                        <Components.ControlLabel input_id={this.props.cmsOptions.id}
                            label_class={this.props.labelClass}>
                            {this.props.cmsOptions.label}
                            <Components.HelpIcon id={"help-" + this.props.cmsOptions.id}
                                content={this.props.cmsOptions.help}/>
                        </Components.ControlLabel>
                        <div className="col-md-5">
                            <select ref={this.props.cmsOptions.id} id={this.props.cmsOptions.id}
                                defaultValue={value} className="form-control">{cmsOptions}</select>
                        </div>
                    </div>
            } else {
                var titleVal, applicationVal, conditionsVal, descriptionVal;
                if (this.props.values) {
                    titleVal = this.props.values['text_options'][this.props.textOptions.titleId];
                    applicationVal = this.props.values['text_options'][this.props.textOptions.applicationId];
                    conditionsVal = this.props.values['text_options'][this.props.textOptions.conditionsId];
                    descriptionVal = this.props.values['text_options'][this.props.textOptions.descriptionId];
                }
                types = [
                    <div className="form-group" key={this.props.textOptions.titleId}>
                        <Components.ControlLabel input_id={this.props.textOptions.titleId}
                            label_class={this.props.labelClass}>
                            {this.props.textOptions.titleLabel}
                            <Components.HelpIcon id={"help-" + this.props.textOptions.titleId}
                                content={this.props.textOptions.titleHelp}/>
                        </Components.ControlLabel>
                        <div className="col-md-5">
                            <input id={this.props.textOptions.titleId} ref={this.props.textOptions.titleId}
                                placeholder={this.props.textOptions.titlePlaceholder} className="form-control"
                                onChange={this.props.onTextChange} defaultValue={titleVal}/>
                        </div>
                    </div>,
                    <div className="form-group" key={this.props.textOptions.applicationId}>
                        <Components.ControlLabel input_id={this.props.textOptions.applicationId}
                            label_class={this.props.labelClass}>
                            {this.props.textOptions.applicationLabel}
                            <Components.HelpIcon id={"help-" + this.props.textOptions.applicationId}
                                content={this.props.textOptions.applicationHelp}/>
                        </Components.ControlLabel>
                        <div className="col-md-5">
                            <input id={this.props.textOptions.applicationId} ref={this.props.textOptions.applicationId}
                                placeholder={this.props.textOptions.applicationPlaceholder} className="form-control"
                                onChange={this.props.onTextChange} defaultValue={applicationVal}/>
                        </div>
                    </div>,
                    <div className="form-group" key={this.props.textOptions.conditionsId}>
                        <Components.ControlLabel input_id={this.props.textOptions.conditionsId}
                            label_class={this.props.labelClass}>
                            {this.props.textOptions.conditionsLabel}
                            <Components.HelpIcon id={"help-" + this.props.textOptions.conditionsId}
                                content={this.props.textOptions.conditionsHelp}/>
                        </Components.ControlLabel>
                        <div className="col-md-5">
                            <input id={this.props.textOptions.conditionsId} ref={this.props.textOptions.conditionsId}
                                placeholder={this.props.textOptions.conditionsPlaceholder} className="form-control"
                                onChange={this.props.onTextChange} defaultValue={conditionsVal}/>
                        </div>
                    </div>,
                    <div className="form-group" key={this.props.textOptions.descriptionId}>
                        <Components.ControlLabel input_id={this.props.textOptions.descriptionId}
                            label_class={this.props.labelClass}>
                            {this.props.textOptions.descriptionLabel}
                            <Components.HelpIcon id={"help-" + this.props.textOptions.descriptionId}
                                content={this.props.textOptions.descriptionHelp}/>
                        </Components.ControlLabel>
                        <div className="col-md-5">
                            <input id={this.props.textOptions.descriptionId} ref={this.props.textOptions.descriptionId}
                                placeholder={this.props.textOptions.descriptionPlaceholder} className="form-control"
                                onChange={this.props.onTextChange} defaultValue={descriptionVal}/>
                        </div>
                    </div>
                ]
            }
            return (
                <div className="col-md-offset-1">{types}</div>
            );
        },
        getDefaultProps: function () {
            return {
                cmsOptions: {
                    label: "Block Handle",
                    id: "block-handle",
                    help: "Select a cms block handle"
                },
                textOptions: {
                    titleLabel: "Promotion Title",
                    titleId: "promotion-title",
                    titleHelp: "Add custom title or leave empty for default",
                    titlePlaceholder: "(USE MAIN TITLE)",
                    applicationLabel: "Show Application Type",
                    applicationId: "show-application",
                    applicationHelp: "?",
                    applicationPlaceholder: "Coupon Code vs Auto Apply (Show Code if applies)",
                    conditionsLabel: "Show Conditions",
                    conditionsId: "show-conditions",
                    conditionsHelp: "(USE MAIN DESC) or add custom text",
                    conditionsPlaceholder: "(USE MAIN DESC)",
                    descriptionLabel: "Show Description",
                    descriptionId: "show-description",
                    descriptionHelp: "(USE MAIN DESC) or add custom text",
                    descriptionPlaceholder: "(USE MAIN DESC)"
                },
                labelClass: "col-md-3",
                cmsBlocksUrl: 'conditions/cmsblocks'
            }
        },
        componentDidMount: function () {
            this.initCmsBlockSelect();
        },
        componentDidUpdate: function () {
            this.initCmsBlockSelect();
        },
        initCmsBlockSelect: function () {
            var cmsSelect = this.refs[this.props.cmsOptions.id];
            if (cmsSelect) {
                $(cmsSelect.getDOMNode())
                    .select2({minimumResultsForSearch: 15})
                    .on('change', this.props.onCmsChange).select2('val', this.props.values[this.props.cmsOptions.id]);
            }
        }
    });

    var AddPromoDisplayApp = React.createClass({
        render: function () {
            var other = _.omit(this.props, ['data']);
            return (
                <div id="add-promo-display">
                    {this.props.data.map(function (item) {
                        if (item['data_serialized']) {
                            $.extend(item, JSON.parse(item['data_serialized']));
                            delete item['data_serialized'];
                        }
                        return <AddPromoDisplayItem {...other} data={item} key={item.id} id={"add-promo-item" + item.id}/>
                    }.bind(this))}
                </div>
            );
        },
        getDefaultProps: function () {
            return {cmsBlocksUrl: 'conditions/cmsblocks'}
        }
    });

    var AddPromoDisplayItem = React.createClass({
        render: function () {
            var contentValue, content, conditions = [];

            if(this.state.delete) {
                content = <input key={'delete' + this.props.data.id} type="hidden" name={"display[" + this.props.data.id + "][delete]"} value="1"/>
            } else {
                if (this.props.data.content_type == 'cms_block') {
                    contentValue = this.props.data.cms_block_handle;
                } else if (this.props.data.content_type == 'html') {
                    contentValue = this.props.data.html_content
                } else if (this.props.data.content_type == 'md') {
                    contentValue = this.props.data.text_content;
                }

                //for(var c in this.props.data.conditions) {
                //    if(this.props.data.conditions.hasOwnProperty(c)) {
                //        conditions.push(<AddPromoDisplayCondition key={c + '-' + this.props.data.id} customerGroups={this.props.customerGroups}
                //            data_id={this.props.data.id} type={c} value={this.props.data.conditions[c]}/>)
                //    }
                //}
                conditions = this.props.data.conditions.map(function (condition, idx) {
                    //console.log(condition);
                    for (var c in condition) {
                        return <AddPromoDisplayCondition key={c + '-' + idx + '-' + this.props.data.id} customerGroups={this.props.customerGroups}
                            data_id={this.props.data.id} type={c} value={condition[c]} onRemove={this.props.removeCondition}/>;
                    }
                }.bind(this));
                content =
                    <div key={'add-promo-' + this.props.data.id} className="add-promo-display-item" style={{position: "relative"}}>
                        <hr/>
                        <a href="#" className="btn-remove" id={"remove_promo_display_btn_" + this.props.data.id}>
                            <span className="icon-remove-sign"></span>
                        </a>
                        <div className="form-group">
                            <Components.ControlLabel input_id={"display-page_type-" + this.props.data.id}
                                label_class={this.props.labelClass}>
                                {this.props.typeLabel}
                                <Components.HelpIcon id={"display-page_type-help-" + this.props.data.id}
                                    content={this.props.typeHelp}/>
                            </Components.ControlLabel>
                            <div style={divStyle}>
                                <select id={"display-page_type-" + this.props.data.id}
                                    ref={"display-page_type-" + this.props.data.id} className="form-control"
                                    name={"display[" + this.props.data.id + "][page_type]"}
                                    defaultValue={this.props.data.page_type}>
                                    <option value="home_page">Home Page</option>
                                    <option value="category_page">Category Page</option>
                                    <option value="product_page">Product Page</option>
                                    <option value="cart_page">Cart</option>
                                    <option value="success_page">Success Page</option>
                                    <option value="custom_hook">Custom Hook</option>
                                </select>
                            </div>
                            <div style={divStyle}>
                                <select id={"display-page_location-" + this.props.data.id} className="form-control"
                                    name={"display[" + this.props.data.id + "][page_location]"}
                                    defaultValue={this.props.data.page_location}>
                                    {this.props.locationPageOptions[this.state.page_type ? this.state.page_type : this.props.data.page_type].map(function (option) {
                                        return <option key={option}>{option}</option>
                                    })}
                                </select>
                            </div>
                        </div>
                        <div className="form-group">
                            <Components.ControlLabel input_id={"display-content_type-" + this.props.data.id}
                                label_class={this.props.labelClass}>
                                {this.props.contentTypeLabel}
                                <Components.HelpIcon id={"display-content_type-help-" + this.props.data.id}
                                    content={this.props.contentTypeHelp}/>
                            </Components.ControlLabel>
                            <div style={divStyle}>
                                <select id={"display-content_type-" + this.props.data.id}
                                    ref={"display-content_type-" + this.props.data.id} className="form-control"
                                    name={"display[" + this.props.data.id + "][content_type]"}
                                    defaultValue={this.props.data.content_type}>
                                    <option value="html">Text (Html)</option>
                                    <option value="md">Text (Markdown)</option>
                                    <option value="cms_block">CMS Block</option>
                                </select>
                            </div>
                        </div>
                        <div className="form-group">
                            <AddPromoDisplayItemContent id={"display-content-" + this.props.data.id} ref={"display-content-" + this.props.data.id}
                                data_id={this.props.data.id} value={contentValue} base_url={this.props.base_url} cmsBlocksUrl={this.props.cmsBlocksUrl}
                                type={this.state.content_type ? this.state.content_type : this.props.data.content_type}/>
                        </div>
                        <div className="form-group">
                            <Components.ControlLabel input_id={"display-match-" + this.props.data.id}
                                label_class={this.props.labelClass}>
                                {this.props.conditionsLabel}
                                <Components.HelpIcon id={"display-match-help-" + this.props.data.id}
                                    content={this.props.conditionsHelp}/>
                            </Components.ControlLabel>
                            <div style={divStyle}>
                                <select id={"display-match-" + this.props.data.id}
                                    ref={"display-match-" + this.props.data.id} className="form-control"
                                    name={"display[" + this.props.data.id + "][data][match]"}
                                    defaultValue={this.props.data.match}>
                                    <option value="always">Show Always</option>
                                    <option value="all">When ALL Conditions Match</option>
                                    <option value="any">When ANY Conditions Match</option>
                                </select>
                            </div>
                            <div style={divStyle}>
                                <select id={"display-add-condition-" + this.props.data.id}
                                    ref={"display-add-condition-" + this.props.data.id} className="form-control">
                                    <option value="-1">Add Condition...</option>
                                    <option value="promo_conditions_match">Promo Conditions Met</option>
                                    <option value="customer_groups">Customer Group</option>
                                </select>
                            </div>
                        </div>
                        <div className="col-md-offset-1" ref={"display-add-conditions-container" + this.props.data.id}>{conditions}</div>
                    </div>;
            }

            return (
                content
            );
        },
        getDefaultProps: function () {
            return {
                labelClass: "col-md-3",
                typeLabel: "Type & Location",
                typeHelp: "On which page and where on the page to place promo details.",
                contentTypeLabel: "What to Show",
                contentTypeHelp: "What to show as details",
                conditionsLabel: "CONDITIONS",
                conditionsHelp: "Conditions when to show promo details",
                locationPageOptions: {
                    home_page: ["Below Product Name", "Below Add To Cart Block", "Above Description  Block", "Above Add To Cart Button", "home"],
                    category_page: ["Below Product Name", "Below Add To Cart Block", "Above Description  Block", "Above Add To Cart Button", "category"],
                    product_page: ["Below Product Name", "Below Add To Cart Block", "Above Description  Block", "Above Add To Cart Button", "product"],
                    cart_page: ["Below Product Name", "Below Add To Cart Block", "Above Description  Block", "Above Add To Cart Button", "cart"],
                    success_page: ["Below Product Name", "Below Add To Cart Block", "Above Description  Block", "Above Add To Cart Button", "success"],
                    custom_hook: ["Below Product Name", "Below Add To Cart Block", "Above Description  Block", "Above Add To Cart Button", "hook"]
                }
            }
        },
        getInitialState: function () {
            return {};
        },
        componentDidMount: function () {
            if (!this.state.delete) {
                $('select', this.getDOMNode()).select2({minimumResultsForSearch: 15, dropdownAutoWidth: true});

                // handle page type change, home_page, product_page etc.
                $(this.refs["display-page_type-" + this.props.data.id].getDOMNode()).on("change", function (e) {
                    this.setState({page_type: e.val});
                }.bind(this));

                // handle display content type change html, md, cms_block
                $(this.refs["display-content_type-" + this.props.data.id].getDOMNode()).on("change", function (e) {
                    this.setState({content_type: e.val});
                }.bind(this));

                $(this.refs["display-add-condition-" + this.props.data.id].getDOMNode()).on("change", function (e) {
                    var val = e.val;
                    if($.trim(val) === '') {
                        return; // empty value, do nothing
                    }
                    $(e.target).select2('val', '-1', false); // reset to placeholder value without change event
                    this.props.addCondition(this.props.data.id, val);
                }.bind(this));

                // handle remove display item, set it to be deleted
                $("#remove_promo_display_btn_" + this.props.data.id).on('click', function (e) {
                    e.preventDefault();
                    //console.log(this);
                    if (confirm("Do you really want to remove display settings?")) {
                        this.setState({"delete": true});
                    }
                }.bind(this));
            }
        }
    });
    var AddPromoDisplayCondition = React.createClass({
        render: function () {
            var condition = '', type= this.props.type, val = this.props.value, id = this.props.data_id;
            var inputName = "display[" + this.props.data_id + "][data][conditions][][" + type + "]";
            var labelFor = "label-for-" + type + "-" + id, key = "value-for-" + type + "-" + id;
            var delBtn =
                <Components.Button className="btn-link btn-delete" onClick={this.onRemove}
                    type="button" style={ {paddingRight: 10, paddingLeft: 10} } key={"rm-for-" + type + "-" + id}>
                    <span className="icon-trash"></span>
                </Components.Button>;
            if(type === 'promo_conditions_match') {
                condition = [
                    <Components.ControlLabel input_id={type + "-" + id} key={ labelFor }
                        label_class="col-md-4">
                                {delBtn}
                                {this.props.promoMetLabel}
                    </Components.ControlLabel>,
                    <div key={key} style={divStyle}>
                        <Components.YesNo name={inputName} value={val}/>
                    </div>
                ];
            } else if(type === 'customer_groups') {
                var customerOptions = _.map(this.props.customerGroups, function (groupName, groupCode) {
                    return <option key={groupCode + id} value={groupCode}>{groupName}</option>;
                });
                condition = [
                    <Components.ControlLabel input_id={type + "-" + id} key={ labelFor }
                        label_class="col-md-4">
                                {delBtn}
                                {this.props.customerGroupLabel}
                    </Components.ControlLabel>,
                    <div key={key} style={divStyle}>
                        <select name={inputName} defaultValue={val} className="form-control">
                            {customerOptions}
                        </select>
                    </div>
                ];
            }
            return (<div id={type + '-' + id} className="form-group">{condition}</div>);
        },
        getDefaultProps: function () {
            return {
                promoMetLabel: "Display when promo conditions have been met",
                customerGroupLabel: "Display when customer group is"
            };
        },
        onRemove: function () {
            return this.props.onRemove(this.props.data_id, this.props.type, this.props.value);
        }
    });
    var AddPromoDisplayItemContent = React.createClass({
        render: function () {
            var content = '';
            switch(this.props.type) {
                case 'html':
                    content = <div><textarea rows="5" key={'wysywig-' + this.props.id}
                        className="form-control ckeditor js-desc-wysiwyg"
                        id={this.props.id}
                        name={"display[" + this.props.data_id + "][data][html_content]"} defaultValue={this.props.value}></textarea>
                        <textarea className="form-control js-desc-wysiwyg" id={this.props.id + "-validation"} key={'wysywig-val-' + this.props.id}
                            style={{display: "none"}} defaultValue={this.props.value}></textarea>
                    </div>;
                    break;
                case 'cms_block':
                    var cmsOptions = this.getCmsOptions();
                    content =
                        <div>
                            <Components.ControlLabel input_id={this.props.id}
                                label_class={this.props.labelClass}>Block Handle
                                <Components.HelpIcon id={"help-" + this.props.id}
                                    content="Select a cms block handle"/>
                            </Components.ControlLabel>
                            <div className="col-md-5">
                                <select ref={this.props.id} id={this.props.id} key={'cms-block-' + this.props.id}
                                    name={"display[" + this.props.data_id + "][data][cms_block_handle]"}
                                    defaultValue={this.props.value} className="form-control">{cmsOptions}</select>
                            </div>
                        </div>;
                    break;
                case 'md':
                    content = <textarea rows="5"
                        id={this.props.id}
                        name={"display[" + this.props.data_id + "][data][text_content]"}
                        className="form-control"
                        placeholder="Text content here ..." defaultValue={this.props.value}></textarea>;
                    break;
            }

            return (
                <div className="col-md-offset-3">
                    {content}
                </div>
            );
        },
        initCmsBlockSelect: function () {
            if (this.props.type == 'cms_block') {
                var $cmsSelect = $('#' + this.props.id);
                if ($cmsSelect.length) {
                    $cmsSelect.select2({minimumResultsForSearch: 15})
                        .val(this.props.value);
                }
            }
        },
        initRichEditor: function () {
            if (this.props.type == 'html' && !CKEDITOR.instances[this.props.id]) {
                CKEDITOR.replace(this.props.id);
            } else if (this.props.type !== 'html' && CKEDITOR.instances[this.props.id]) {
                try {
                    CKEDITOR.instances[this.props.id].destroy();
                } catch (e) {
                    delete CKEDITOR.instances[this.props.id];
                }
            }
        },
        componentDidMount: function () {
            this.initRichEditor();
            this.initCmsBlockSelect();
        },
        componentDidUpdate: function () {
            this.initRichEditor();
            this.initCmsBlockSelect();
        },
        componentWillUnmount: function () {
            if (this.props.type == 'html' && CKEDITOR.instances[this.props.id]) {
                try {
                    CKEDITOR.instances[this.props.id].destroy();
                } catch (e) {
                    delete CKEDITOR.instances[this.props.id];
                }
            }
        },
        getCmsOptions: function () {
            if (!cmsBlocks) {
                //cmsBlocks = [];
                var self = this;
                var url = this.props.base_url + '/' + this.props.cmsBlocksUrl;
                getCmsBlocks(url, function (result) {
                    if (result.items) {
                        cmsBlocks = result.items.map(function (item) {
                            return <option key={item.id} value={item.text}>{item.text}</option>
                        });
                        self.forceUpdate();
                    }
                });
            }
            return cmsBlocks;
        }
    });

    function renderCentralPageApp(properties, container) {
        React.render(<CentralPageApp {...properties} id="central-page-app"/>, container);
    }

    function renderAddPromoDisplayApp(properties, container) {
        React.render(<AddPromoDisplayApp {...properties} id="add-promo-display-app"/>, container);
    }

    var lastNewId = 1;
    function newItem() {
        return {
            id: "new" + (lastNewId++),
            match: "always",
            content_type: "html",
            page_type: "home_page",
            page_location: "bellow_product_name"
        };
    }
    return {
        initCentralPageApp: function (options) {
            var $selector = options.selector, $dataSerialized = $('#' + options.promo_serialized);
            if (!$selector.length) {
                console.warn("Display type selector not found");
                return;
            } else if (!$dataSerialized.length) {
                console.warn("Data serialized field not found");
                return;
            }

            var val = $dataSerialized.val();
            if (val) {
                try {
                    options.data = JSON.parse(val);
                } catch (e) {
                    console.log(e);
                }
            }

            function updateDataSerialized() {
                if ($dataSerialized.length) {
                    var values = options.data;
                    $dataSerialized.val(JSON.stringify(values));
                } else {
                    console.error("Cannot find serialized options element");
                }
            }

            var type = $selector.val();
            var centralAppProps = {
                type: type,
                base_url: options.base_url,
                values: options.data['display_type_details'],
                onCmsChange: function (e) {
                    //console.log(e);
                    var val = e.val; // select2 sets new value in val field of event
                    if (!val) {
                        val = $(e.target).val();
                    }
                    if (options.data['display_type_details']) {
                        options.data['display_type_details']['block-handle'] = val;
                    } else {
                        options.data['display_type_details'] = {'block-handle': val};
                    }
                    updateDataSerialized();
                },
                onTextChange: function (e) {
                    var $el = $(e.target);
                    var id = $el.attr('id');
                    var val = $el.val();
                    //console.log(id, val);
                    var data = options.data['display_type_details'] || {};
                    var textOptions = data['text_options'] || {};
                    textOptions[id] = val;
                    if (options.data['display_type_details']) {
                        options.data['display_type_details']['text_options'] = textOptions;
                    } else {
                        options.data['display_type_details'] = {'text_options': textOptions};
                    }
                    updateDataSerialized();
                }
            };

            renderCentralPageApp(centralAppProps, options.container);
            $selector.on('change', function (e) {
                e.preventDefault();
                centralAppProps.type = $(this).val();
                centralAppProps.values = options.data['display_type_details'];
                renderCentralPageApp(centralAppProps, options.container);
            });
        },
        initAddPromoDisplayApp: function (options) {
            console.log(options);
            var $addDisplayBtn = options.addDisplayBtn
            var properties = {
                data: options.promoDisplayData,
                base_url: options.base_url,
                customerGroups: options.customerGroups,
                addCondition: function (id, conditionType) {
                    var newCond = {};
                    newCond[conditionType] = '';
                    _.each(options.promoDisplayData, function (item) {
                        if(item.id == id) {
                            item.conditions.push(newCond);
                        }
                    });
                    renderAddPromoDisplayApp(properties, options.container);
                },
                removeCondition: function (id, conditionType, value) {
                    _.each(options.promoDisplayData, function (item) {
                        if (item.id == id) {
                            for(var i = 0; i < item.conditions.length; i++) {
                                var cond = item.conditions[i];
                                if(cond[conditionType] !== undefined && cond[conditionType] == value) {
                                    item.conditions.splice(i, 1);
                                    break;
                                }
                            }
                        }
                    });
                    renderAddPromoDisplayApp(properties, options.container);
                }
            };
            options.addDisplayBtn.on("click", function (e) {
                e.preventDefault();
                var item = newItem();
                options.promoDisplayData.push(item);
                renderAddPromoDisplayApp(properties, options.container);
            });
            renderAddPromoDisplayApp(properties, options.container);
        }
    }
});
var divStyle = {float: 'left', marginLeft: 5};
