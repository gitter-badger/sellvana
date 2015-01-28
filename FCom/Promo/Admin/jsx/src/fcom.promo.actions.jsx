/**
 * Created by pp on 22.Dec14.
 */

define(['react', 'jquery', 'jsx!fcom.components', 'jsx!fcom.promo.common', 'fcom.locale', 'select2'], function (React, $, Components, Common, Locale) {
    var Type = React.createClass({
        render: function () {
            var cls = this.props.select2 ? "to-select2 " : "";
            if (this.props.className) {
                cls += this.props.className;
            }
            return (
                <div className={this.props.containerClass}>
                    <div className="col-md-10">
                    <select className={cls} onChange={this.onChange} defaultValue={this.props.value}>
                        {this.props.totalType.map(function (type) {
                            return <option value={type.id} key={type.id}>{type.label}</option>
                        })}
                    </select>
                    </div>
                    {this.props.children}
                </div>
            );
        },
        value: null,
        onChange: function (e) {
            this.value = $('select', this.getDOMNode()).select2('val');
            if(this.props.onChange) {
                this.props.onChange(e);
            }
        },
        serialize: function () {
            return this.value || $('select', this.getDOMNode()).select2('val');
        },
        getDefaultProps: function () {
            return {
                totalType: [{id: "pcnt", label: "% Off"}, {id: "amt", label: "$ Amount Off"}],
                select2: true,
                containerClass: "col-md-2",
                className: "form-control"
            };
        }, componentDidMount: function () {
            $('select.to-select2', this.getDOMNode()).select2().on('change', this.onChange);
        }
    });

    var DiscountDetailsCombination = React.createClass({
        render: function () {
            return (
                <div>
                    <div className="col-md-8">
                        <input type="text" readOnly="readonly" ref="attributesResume" id="attributesResume" className="form-control" value={this.state.valueText}/>
                    </div>
                    <div className="col-md-4">
                        <Components.Button type="button" className="btn-primary"
                            ref={this.props.configureId} onClick={this.handleConfigure}>Configure</Components.Button>
                    </div>
                </div>
            );
        },
        getInitialState: function () {
            return {value: ''};
        },
        getDefaultProps: function () {
            return {
                rowClass: "attr-combination",
                label: "Combination",
                configureId: "attributeCombinationConfigure",
                type: 'comb'
            };
        },
        modal: null,
        modalContent: null,
        handleConfigure: function () {
            var modal = <Components.Modal onConfirm={this.handleConditionsConfirm} onCancel={this.handleConditionsCancel}
                title="Product Combination Configuration" onLoad={this.registerModal} onUpdate={this.registerModal}>
                <DiscountDetailsCombinationsModalContent  baseUrl={this.props.options.base_url} onLoad={this.registerModalContent} />
            </Components.Modal>;

            React.render(modal, this.props.modalContainer.get(0));
        },
        handleConditionsCancel: function (modal) {
            modal.close();
            var mc = this.modalContent;
            mc.setState({fields: [], values:{}});
        },
        handleConditionsConfirm: function (modal) {
            var mc = this.modalContent;
            var value = mc.serialize();
            var valueText = mc.serializeText();
            this.setState({
                valueText: valueText,
                value: value
            });
            modal.close();
        },
        registerModal: function (modal) {
            this.modal = modal;
            this.openModal(modal);
        },
        registerModalContent: function (content) {
            this.modalContent = content;
        },
        openModal: function (modal) {
            modal.open();
        }
    });

    var DiscountDetailsCombinationsModalContent = React.createClass({
        mixins: [Common.select2QueryMixin],
        render: function () {
            var fieldUrl = this.props.baseUrl + this.props.urlField;
            var paramObj = {};
            return (
                <div className="attribute-combinations form-horizontal">
                    <div className="form-group">
                        <div className="col-md-5">
                            <select ref="combinationType" className="form-control to-select2" id="attribute-combination-type">
                                <option value="0">All Conditions Have to Match</option>
                                <option value="1">Any Condition Has to Match</option>
                            </select>
                        </div>
                        <div className="col-md-5">
                            <input ref="combinationField" className="form-control"/>
                        </div>
                    </div>
                    {this.state.fields.map(function (field) {
                        paramObj['field'] = field.field;
                        var url = fieldUrl + '/?' + $.param(paramObj);
                        return <DiscountDetailsCombinationsModalField label={field.label} url={url} key={field.field}
                            id={field.field} input={field.input} removeField={this.removeField} ref={field.field} onChange={this.elementChange}/>
                    }.bind(this))}
                </div>
            );
        },
        serialize: function () {
            return this.state.values;
        },
        serializeText: function () {
            var text, glue, fieldTexts = [];
            var allShouldMatch = $(this.refs['combinationType'].getDOMNode()).val(); // && or ||
            if(allShouldMatch == 1) {
                glue = " or ";
            } else {
                glue = " and ";
            }
            for(var field in this.refs) {
                if(field == 'combinationType' || field == 'combinationField') {
                    continue;
                }
                if(this.refs[field]) {
                    var ref = this.refs[field];
                    var refText = ref.serializeText();
                    fieldTexts.push(refText);
                }
            }

            text = fieldTexts.join(glue);
            return text;
        },
        addField: function () {
            var fieldCombination = this.refs['combinationField'].getDOMNode();
            var fieldValue = $(fieldCombination).select2("data");
            if(null == fieldValue || fieldValue == []) {
                return;
            }
            var fields = this.state.fields;
            fields.push({label: fieldValue.text, field: fieldValue.id, input: fieldValue.input});
            $(fieldCombination).select2("val", "", false);
            this.setState({fields: fields});
        },
        removeField: function (id) {
            var fields = this.state.fields;
            fields = fields.filter(function (field) {
                return field.field != id;
            });
            this.setState({fields: fields});
        },
        getInitialState: function () {
            return {fields: [], values: {}};
        },
        getDefaultProps: function () {
            return {
                labelCombinationField: Locale._("Add a Field to Condition..."),
                urlField: "conditions/attributes_field",
                url: 'conditions/attributes_list'
            };
        },
        url: '',
        componentDidMount: function () {
            var fieldCombination = this.refs['combinationField'];
            var self = this;
            this.url = this.props.baseUrl + this.props.url;
            $(fieldCombination.getDOMNode()).select2({
                placeholder: self.props.labelCombinationField,
                multiple: false,
                closeOnSelect: true,
                query: self.select2query,
                dropdownCssClass: "bigdrop",
                dropdownAutoWidth: true,
            }).on('change', this.addField);
            $('.to-select2', this.getDOMNode()).select2().on('change', this.elementChange);
            if (typeof this.props.onLoad == 'function') {
                this.props.onLoad(this);
            }
        },
        shouldUpdate: true,
        shouldComponentUpdate: function () {
            var upd = this.shouldUpdate;
            if(!upd) { // shouldUpdate is one time flag that should be set only specifically and then dismissed
                this.shouldUpdate = true;
            }
            return upd;
        },
        elementChange: function (e) {
            var target = e.target;
            var val = e.val;
            var values = this.state.values;
            values[target.id] = val;
            if(val) {
                this.shouldUpdate = false; // no update needed, just capturing values
                this.setState({values: values});
            }
        }
    });

    var DiscountDetailsCombinationsModalField = React.createClass({
        mixins:[Common.select2QueryMixin],
        render: function () {
            var inputType = this.props.input;
            var opts = this.getOpts();
            var fieldId = "fieldCombination." + this.props.id;
            var value;
            if (this.props.data && this.props.data.length) {
                if ($.isArray(this.props.data)) {
                    value = this.props.data.join(",");
                } else {
                    value = this.props.data;
                }
            }
            var input = <input className="form-control required" type="text" id={fieldId} ref={fieldId}
                onChange={this.onChange} defaultValue={value}/>;
            if(this.props.numeric_inputs.indexOf(inputType) != -1) {
                if (inputType == 'number') {
                    if(this.state.range === false) {
                        input = <input className="form-control required" type="number" step="any" id={fieldId}
                            ref={fieldId} style={{width: "auto"}} onChange={this.onChange} defaultValue={value}/>;
                    } else {
                        value = this.props.data;
                        var min, max;
                        if (value.length > 0) {
                            min = value[0]
                        }

                        if (value.length > 1) {
                            max = value[1];
                        }
                        input = <div id={fieldId} ref={fieldId} className="input-group">
                            <input className="form-control required" type="number" step="any" placeholder="Min"
                                style={{width: "50%"}} onChange={this.onChange} defaultValue={min} id={fieldId + ".min"}/>
                            <input className="form-control required" type="number" step="any" placeholder="Max"
                                style={{width: "50%"}} onChange={this.onChange} defaultValue={max} id={fieldId + ".max"}/>
                        </div>;
                    }
                } else if (inputType == 'date' || inputType == 'time') {
                    var singleMode = true;
                    if (this.state.range === true) {
                        singleMode = false;
                    }
                    input = <div className="input-group">
                        <span className="input-group-addon"><i className="glyphicon glyphicon-calendar"></i></span>
                        <input className="form-control required" type="text" id={fieldId} ref={fieldId}
                            dataMode={singleMode} onChange={this.onChange} defaultValue={value}/>
                    </div>
                }
            } else if(inputType == 'select'){
                input = <input className="form-control required" type="hidden" id={fieldId} ref={fieldId}
                    defaultValue={value}/>;
            } else if(this.props.bool_inputs.indexOf(inputType) != -1) {
                input = <Components.YesNo  id={fieldId} ref={fieldId}  onChange={this.onChange} defaultValue={value}/>;
            }
            return (
                <Common.Row rowClass={this.props.rowClass} label={this.props.label} onDelete={this.remove}>
                    <div className="col-md-4">
                        <Common.Compare opts={ opts } id={"fieldCompare." + this.props.id} value={this.props.filter}
                            ref={"fieldCompare." + this.props.id} onChange={this.onCompareChange}/>
                    </div>
                    <div className="col-md-5">{input}</div>
                </Common.Row>
            );
        },
        values: {},
        getOpts: function () {
            var opts = this.props.opts;
            var inputType = this.props.input;
            if (inputType == 'text') {
                opts = opts.concat(this.props.opts_text);
            } else if (this.props.numeric_inputs.indexOf(inputType) != -1) {
                opts = opts.concat(this.props.opts_numeric);
            }
            return opts;
        },
        serialize: function () {
            var data = {
                field: this.props.id
            };
            data.filter = this.values["fieldCompare." + this.props.id] || $(this.refs["fieldCompare." + this.props.id].getDOMNode()).val();
            var $fieldComb = $(this.refs["fieldCombination." + this.props.id].getDOMNode());
            try{
                $fieldComb.val();
            } catch (e){
                $('input', $fieldComb).trigger('change'); // will it work?
            }
            data.value = this.values["fieldCombination." + this.props.id] || $fieldComb.val();
            data.label = this.props.label;
            data.input = this.props.input;

            return data;
        },
        serializeText: function () {
            var type = this.getInputType();
            var range = this.state.range;
            var text = this.props.label;
            var opts = this.getOpts();
            var opt = this.refs["fieldCompare." + this.props.id];
            var optext = $(opt.getDOMNode()).val();// getting compare operator from element because it might not of been changed
            for (var i = 0; i < opts.length; i++) {
                var o = opts[i];
                if (o.id == optext) {
                    text += " " + o.label;
                    break;
                }
            }

            var value = this.values["fieldCombination." + this.props.id];
            if (value) {
                if ($.isArray(value)) {
                    value = value.join(", ");
                }

                if (type == 'bool') {
                    value = (value == 0) ? Locale._("No") : Locale._("Yes");
                }
                // todo handle numeric ranges and dates

                text += " " + value;
            }

            return text;
        },
        remove: function () {
            if(this.props.removeField) {
                this.props.removeField(this.props.id);
            }
        },
        getInitialState: function () {
            return {
                range: false
            };
        },
        getDefaultProps: function () {
            return {
                label: Locale._("Unknown"),
                url: "",
                fcLabel: "",
                opts: [ // base options, for bool and select fields
                    {id:"is", label: "is"},
                    {id:"is_not", label: "is not"},
                    {id:"empty", label: "has no value"}
                ],
                opts_text:[ // add to base for text fields
                    {id:"contains", label: "contains"}
                ],
                opts_numeric: [ // add to base for numeral fields
                    {id: "lt", label: "is less than"},
                    {id: "lte", label: "is less than or equal"},
                    {id: "gt", label: "is greater than"},
                    {id: "gte", label: "is greater than or equal"},
                    {id: "between", label: "is between"}
                ],
                numeric_inputs: ['number', 'date', 'time'],
                bool_inputs: ['yes_no']
            };
        },
        componentDidMount: function () {
            var inputType = this.props.input;
            switch (inputType) {
                case 'select':
                    this.initSelectInput();
                    break;
                case 'date':
                    this.initDateInput();
                    break;
                default :
                    break;
            }
            //$('select.to-select2', this.getDOMNode()).select2({minimumResultsForSearch:15}).on('change', this.props.onChange);
        },
        //componentDidUpdate: function () {
        //    this.componentDidMount();
        //},
        getInputType: function () {
            var inputType = this.props.input;
            var type = 'text';
            if (this.props.numeric_inputs.indexOf(inputType) != -1) {
                type = 'numeric';
                if (inputType == 'date' || inputType == 'time') {
                    type = 'date';
                }
            } else if (inputType == 'select') {
                type = 'select';
            } else if (this.props.bool_inputs.indexOf(inputType) != -1) {
                type = 'bool';
            }
            return type;
        },
        onCompareChange: function (e) {
            this.values["fieldCompare." + this.props.id] = e.val;
            if (this.props.numeric_inputs.indexOf(this.props.input) == -1) {
                return;
            }
            var target = e.target;
            var state = {range: false};
            state.range = (target.value == 'between');
            this.setState(state);
        },
        onChange: function (e) {
            var type = this.getInputType();

            var $elem = $(e.target);
            // only select2 event has e.val, for dom inputs it must be added
            if (!e.val) { // for native inputs, use blur event to capture value
                e.value = $elem.val();
            } else {
                e.value = e.val;
            }
            //console.log(e);
            if (this.state.range && type == 'numeric') {
                var id = $elem.attr('id');
                var idArray = id.split('.');
                if(idArray.length > 1) { // id is like field.min/max
                    var minMax = idArray[1]; // min || max
                    // if value is already set in non range mode, it will be scalar, or null if this is first time
                    var value = this.values["fieldCombination." + this.props.id] || [null, null];
                    if (!$.isArray(value)) {
                        //if scalar, dump it and set again
                        value = [null, null];
                    }
                    // min is at index 0, max index 1
                    if ('min' == minMax) {
                        value[0] = e.value;
                    } else {
                        value[1] = e.value;
                    }

                    e.value = value;
                }
            } else if (type == 'date' && !this.state.range) {
                // potentially range of dates
            }
            this.values["fieldCombination." + this.props.id] = e.value;
            if (this.props.onChange) {
                this.props.onChange(e);
            }
        },
        initDateInput: function () {
            var startDate = new Date();
            var s = startDate.getFullYear() + '-' + (startDate.getMonth() + 1) + '-' + startDate.getDate();
            var fieldCombination = this.refs["fieldCombination." + this.props.id];
            var $input = $(fieldCombination.getDOMNode());
            var mode = fieldCombination.props.dataMode;
            var parent = $input.closest('.modal');
            $input.daterangepicker(
                {
                    format: 'YYYY-MM-DD',
                    startDate: s,
                    singleDatePicker: mode,
                    parentEl: parent
                }
            );
        },
        url:'',
        initSelectInput: function () {
            var fieldCombination = this.refs["fieldCombination." + this.props.id];
            var self = this;
            this.url = this.props.url;
            $(fieldCombination.getDOMNode()).select2({
                placeholder: self.props.fcLabel,
                maximumSelectionSize: 4,
                multiple: true,
                closeOnSelect: true,
                query: this.select2query,
                dropdownCssClass: "bigdrop",
                dropdownAutoWidth: true,
                initSelection: self.initSelection
            }).on('change', this.onChange);
        }
    });

    var DiscountSkuCombination = React.createClass({
        mixins: [Common.select2QueryMixin],
        render: function () {
            var value;
            if(this.props.data) {
                if($.isArray(this.props.data)) {
                    value = this.props.data.join(",");
                } else {
                    value = this.props.data;
                }
            }
            return (
                <input type="hidden" id="skuCollectionIds" ref="skuCollectionIds" className="form-control" defaultValue={value}/>
            );
        },
        getDefaultProps: function () {
            return {
                url: 'conditions/products'
            };
        },
        url: '',
        componentDidMount: function () {
            this.buildSelect();
        },
        componentWillUnmount: function () {
            var skuCollectionIds = this.refs['skuCollectionIds'];
            $(skuCollectionIds.getDOMNode()).select2('destroy');
        },
        buildSelect: function () {
            var skuCollectionIds = this.refs['skuCollectionIds'];
            this.url = this.props.options.base_url + this.props.url;
            var self = this;
            $(skuCollectionIds.getDOMNode()).select2({
                placeholder: "Choose products",
                multiple: true,
                closeOnSelect: true,
                dropdownCssClass: "bigdrop",
                dropdownAutoWidth: true,
                formatSelection: function (item) {
                    return item.sku;
                },
                formatResult: function (item) {
                    var markup = '<div class="row-fluid" title="' + item.text + '">' +
                        '<div class="span2">ID: <em>' + item.id + '</em></div>' +
                        '<div class="span2">Name: ' + item.text.substr(0, 20);
                    if(item.text.length > 20) {
                        markup += '...';
                    }
                    markup += '</div>' +
                    '<div class="span2">SKU: <strong>' + item.sku + '</strong></div>' +
                    '</div>';

                    return markup;
                },
                initSelection: self.initSelection,
                query: self.select2query
            });
        },
        getSelectedProducts: function () {
            return $(this.refs['skuCollectionIds'].getDOMNode()).select2('val');
        }
    });

    var DiscountDetails = React.createClass({
        render: function () {
            var details = <span/>;
            if(this.props.type == 'attr_combination') {
                details = <DiscountDetailsCombination id="attrCombination" ref="attrCombination" key="attrCombination"
                    options={this.props.options} modalContainer={this.props.modalContainer} data={this.props.combination}/>
            } else if(this.props.type == 'other_prod') {
                details = <DiscountSkuCombination id="skuCombination" ref="skuCombination" key="skuCombination"
                    options={this.props.options} data={this.props.product_ids}/>;
            }
            return details;
        },
        serialize: function () {
            // todo serialize
            var value = {};
            if(this.props.type == 'other_prod') {
                value.product_ids = this.refs['skuCombination'].getSelectedProducts();
            } else if(this.props.type == 'attr_combination') {
                value.combination = this.refs['attrCombination'].serialize();
            }
            return value;
        }
    });

    var Discount = React.createClass({
        mixins: [Common.removeMixin],
        render: function () {
            return (
                <Common.Row rowClass={this.props.rowClass} label={this.props.label} onDelete={this.remove}>
                    <Type ref="discountType" id="discountType" value={this.state.type} onChange={this.onChange}/>
                    <div className="col-md-7">
                        <div className="col-md-2">
                            <input className="form-control pull-left" ref="discountValue" id="discountValue" type="text" defaultValue={this.state.value}/>
                        </div>
                        <div>
                            <select className="to-select2 form-control" ref="discountScope" id="discountScope" onChange={this.onChange}>
                                {this.props.scopeOptions.map(function (type) {
                                    return <option value={type.id} key={type.id}>{type.label}</option>
                                })}
                            </select>
                        </div>
                        <div>
                            <DiscountDetails type={this.state.scope} options={this.props.options} ref="discountDetails" id="discountDetails"
                                modalContainer={this.props.modalContainer}
                                data={{product_ids: this.state.product_ids, combination: this.state.combination}}/>
                        </div>
                    </div>
                </Common.Row>
            );
        },
        componentDidMount: function () {
            $(this.refs['discountScope'].getDOMNode()).select2().on('change', this.onChange)
        },
        getDefaultProps: function () {
            return {
                label: Locale._('Discount'),
                rowClass: 'discount',
                scopeOptions: [
                    {id:'whole_order', label: 'Whole Order'},
                    {id:'cond_prod', label: 'Product from Conditions'},
                    {id:'other_prod', label: 'Other SKUs'},
                    {id:'attr_combination', label: 'Combination'}
                ]
            }
        },
        getInitialState: function () {
            return {
                value: 0,
                type: '',
                scope: 'whole_order',
                product_ids: [],
                combination: {}
            };
        },
        onTypeChange: function (ev) {
            ev.preventDefault();
            var newType = $(ev.target).val();
            this.onChange();
            this.setState({type: newType});
        },
        onChange: function () {
            var value = {};
            value.value = $(this.refs['discountValue'].getDOMNode()).val();
            value.type = $(this.refs['discountType'].getDOMNode()).val();

            var details = this.refs['discountType'].serialize();
            for(var d in details) {
                if(details.hasOwnProperty(d)) {
                    value[d] = details[d];
                }
            }

            //this.setState(value);

            if(this.props.onUpdate) {
                var updateData = {};
                updateData[this.props.id] = value;
                this.props.onUpdate(updateData);

            }
        }
    });

    var FreeProduct = React.createClass({
        mixins: [Common.select2QueryMixin, Common.removeMixin],
        render: function () {
            var productIds;
            if(this.state.product_ids) {
                if($.isArray(this.state.product_ids)) {
                    productIds = this.state.product_ids.join(",");
                } else {
                    productIds = this.state.product_ids;
                }
            }
            var terms;
            if(this.state.terms) {
                if($.isArray(this.state.terms)) {
                    terms = this.state.terms.join(",");
                } else {
                    terms = this.state.terms;
                }
            }
            return (
                <Common.Row rowClass={this.props.rowClass} label={this.props.label} onDelete={this.remove}>
                    <div className="col-md-3">
                        <input type="hidden" className="form-control" id="productSku" ref="productSku" defaultValue={productIds}/>
                    </div>
                    <div className="col-md-3 form-group">
                        <Components.ControlLabel input_id="productQty">{Locale._('Qty')}</Components.ControlLabel>
                        <div className="col-md-10">
                            <input type="text" className="form-control" id="productQty" ref="productQty" defaultValue={this.state.qty}/>
                        </div>
                    </div>
                    <div className="col-md-3 form-group">
                        <Components.ControlLabel input_id="productTerms">{Locale._('Terms')}</Components.ControlLabel>
                        <div className="col-md-10">
                            <select className="form-control to-select2" id="productTerms" ref="productTerms" multiple="multiple" defaultValue={terms}>
                                <option value="tax">{Locale._("Charge tax")}</option>
                                <option value="sah">{Locale._("Charge S & H")}</option>
                            </select>
                        </div>
                    </div>
                </Common.Row>
            );
        },
        getInitialState: function () {
            return {
                product_ids: [],
                terms: [],
                qty: 0
            }
        },
        getDefaultProps: function () {
            return {
                url: "conditions/products",
                labelSkuField: Locale._("Select product sku")
            }
        },
        url: '',
        componentDidMount: function () {
            var productSku = this.refs['productSku'];
            var self = this;
            this.url = this.props.options.base_url + '/' + this.props.url;
            $(productSku.getDOMNode()).select2({
                multiple: true,
                placeholder: self.props.labelSkuField,
                query: self.select2query,
                dropdownAutoWidth: true,
                formatSelection: function (item) {
                    return item['sku'];
                },
                formatResult: function (item) {
                    var markup = '<div class="row-fluid" title="' + item.text + '">' +
                        '<div class="span2">ID: <em>' + item.id + '</em></div>' +
                        '<div class="span2">Name: ' + item.text.substr(0, 20);
                    if (item.text.length > 20) {
                        markup += '...';
                    }
                    markup += '</div>' +
                    '<div class="span2">SKU: <strong>' + item.sku + '</strong></div>' +
                    '</div>';

                    return markup;
                },
                initSelection: self.initSelection
            });
            $(this.refs['productTerms'].getDOMNode()).select2({initSelection: self.initSelection})
        },
        onChange: function () {
            var value = {};
            value.qty = $(this.refs['productQty'].getDOMNode()).val();
            value.product_ids = $(this.refs['productSku'].getDOMNode()).val();
            value.terms = $(this.refs['productTerms'].getDOMNode()).val();

            //this.setState(value);

            if(this.props.onUpdate) {
                var updateData = {};
                updateData[this.props.id] = value;
                this.props.onUpdate(updateData);

            }
        }
    });

    var Shipping = React.createClass({
        mixins: [Common.select2QueryMixin, Common.removeMixin],
        render: function () {
            var amount = '';
            if(this.state.type != 'free') {
                amount = <input type="number" defaultValue={this.state.amount} id="shippingAmount" ref="shippingAmount" className="form-control" />
            }
            var type = <Type ref="shippingType" id="shippingType" onChange={this.onChange} value={this.state.type}
                    totalType={this.props.fields}/>;
            var label = <Components.ControlLabel label_class="col-md-1" input_id="shippingMethods">{Locale._('For')}</Components.ControlLabel>;
            var input = <input type="hidden" className="form-control" id="shippingMethods" ref="shippingMethods"/>;
            return (
                <Common.Row rowClass={this.props.rowClass} label={this.props.label} onDelete={this.remove}>
                    {type}
                    <div className="col-md-7">
                        <div className={amount? "col-md-2": ""}>{amount}</div>
                        {label}
                        <div className={amount? "col-md-9": "col-md-11"}>{input}</div>
                        {/*if no amount field, make this wider*/}
                    </div>
                </Common.Row>
            );
        },
        onTypeChange: function (ev) {
            ev.preventDefault();
            var newType = $(ev.target).val();
            this.setState({type: newType});
        },
        getDefaultProps: function () {
            return {
                fields: [
                    {id: "pcnt", label: "% Off"}, {id: "amt", label: "$ Amount Off"}, {id: "free", label:"Free"}
                ],
                labelMethodsField: Locale._("Select shipping methods"),
                url: "conditions/shipping"
            };
        },
        getInitialState: function () {
            return {
                type: 'free',
                methods: [],
                amount: 0
            }
        },
        url: '',
        componentDidMount: function () {
            var shippingMethods = this.refs['shippingMethods'];
            var self = this;
            this.url = this.props.options.base_url + '/' + this.props.url + '?' + $.param({field: 'methods'});
            $(shippingMethods.getDOMNode()).select2({
                placeholder: self.props.labelMethodsField,
                multiple: true,
                query: self.select2query,
                dropdownAutoWidth: true,
                initSelection: self.initSelection
            }).on('change', this.onChange);
        },
        onChange: function () {
            var value = {};
            value.type = this.refs['shippingType'].serialize();
            value.methods = $(this.refs['shippingMethods'].getDOMNode()).val();
            if (this.refs['shippingAmount']) {
                value.value = $(this.refs['shippingAmount'].getDOMNode()).val();
            }

            //this.setState(value);

            if(this.props.onUpdate) {
                var updateData = {};
                updateData[this.props.id] = value;
                this.props.onUpdate(updateData);

            }
        }
    });

    var ActionsApp = React.createClass({
        displayName: 'ActionsApp',
        render: function () {
            var children = [];
            var options = this.props.options;
            var mc = this.props.modalContainer;
            var ra = this.removeAction;
            var au = this.actionUpdate;
            for(var action in this.state.data) {
                if(this.state.data.hasOwnProperty(action)) {
                    var actions = this.state.data[action];
                    if($.isArray(actions)) {
                        actions.map(function (field, idx) {
                            //todo make a field based on field
                            var el;
                            var key = action + '-' + idx;
                            switch (action) {
                                case 'discount':
                                    el = <Discount label={Locale._("Discount")} options={options}
                                        key={key} id={key} removeAction={ra} data={field}
                                        modalContainer={mc} onUpdate={au}/>;
                                    break;
                                case 'free_product':
                                    el = <FreeProduct label={Locale._("Auto Add Product To Cart")} options={options}
                                        key={key} id={key} removeAction={ra} onUpdate={au} date={field}/>;
                                    break;
                                case 'shipping':
                                    el = <Shipping label={Locale._("Shipping")} options={options}
                                        key={key} id={key} removeAction={ra} onUpdate={au} date={field}/>;
                                    break;

                            }
                            if(el) {
                                children.push(el);
                            }
                        })
                    } else {
                        console.log(actions, "is not an array");
                    }
                }
            }
            return (
                <div className="actions">
                    {children}
                </div>
            );
        },
        componentWillMount: function () {
            var data = this.state.data;

            if (this.props.actions.length) {
                data = this.props.actions;
                this.setState({data: data});
            }
        },
        componentDidMount: function () {
            var $conditionsSerialized = $('#' + this.props.options.conditions_serialized);
            var data = this.state.data;

            if ($conditionsSerialized.length > 0) {
                try {
                    data = JSON.parse($conditionsSerialized.val());
                    this.setProps({data: data});
                    // todo actually update state
                } catch (e) {
                    console.log(e);
                }
            }

            if (this.props.actionType.length) {
                this.props.actionType.on('change', this.addAction);
            }

            $('select.to-select2', this.getDOMNode()).select2();
        },
        addAction: function () {
            // add condition data to state
            var $actionTypes = this.props.actionType;
            if ($actionTypes.length == 0) {
                return;
            }

            var actionType = $actionTypes.val();
            if(actionType == "-1") {
                return;
            }
            $actionTypes.select2('val', "-1", false);// reset to placeholder value and do NOT trigger change event
            var data = this.state.data;
            var condition = {type: actionType, id: actionType + '-' + this.state.lastActionId};
            data.push(condition);
            this.setState({data: data, lastActionId: (this.state.lastActionId + 1)});
        },
        removeAction: function (actionId) {
            var data = this.state.data;
            data = data.filter(function (field) {
                return field.id != actionId;
            });
            this.setState({data: data});
        },
        actionUpdate: function (data) {
            //todo
            console.log(data);
            var localData = this.state.data;
            for(var type in data) {
                if(data.hasOwnProperty(type)) {
                    var condArray = type.split("-"); // to keep track of multiple conditions of same type shipping-0, shipping-1 ...
                    if(condArray.length == 2) {
                        var rule = condArray[0], idx = condArray[1];
                        localData.rules[rule][idx] = data[type];
                    } else {
                        console.log("wrong condition id: " + type);
                    }
                }
            }
            this.shouldUpdate = false;
            this.props.onUpdate(localData);
            this.setState({data: localData});
        },
        getInitialState: function () {
            return {
                data: {},
                lastActionId: 0
            };
        }
    });
    return ActionsApp;
});
