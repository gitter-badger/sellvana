/**
 * Created by pp on 02-26-2015.
 */
define(['jquery', 'underscore', 'react', 'fcom.locale', 'daterangepicker'], function ($, _, React, Locale) {
    var PricesApp = React.createClass({displayName: "PricesApp",
        render: function () {
            var childProps = _.omit(this.props, ['prices', 'deleted','validatePrices', 'title']);
            return (
                React.createElement("div", {id: "prices"}, React.createElement("h4", null, this.props.title), 
                    React.createElement("div", {className: "form-group", id: "price_captions"}, 
                        React.createElement("div", {style: divStyle}, Locale._("Price Type")), 
                        React.createElement("div", {style: divStyle}, Locale._("Customer group")), 
                        React.createElement("div", {style: divStyle}, Locale._("Site")), 
                        React.createElement("div", {style: divStyle}, Locale._("Currency")), 
                        React.createElement("div", {style: divStyle}, Locale._("Price")), 
                        React.createElement("div", {style: divStyle}, Locale._("Qty (only tier prices)"))
                    ), 
                    _.map(this.props['prices'], function (price) {
                        if (this.props['deleted'] && this.props['deleted'][price.id]) {
                            return React.createElement("input", {key: 'delete-' + price.id, type: "hidden", 
                                name: "price[" + price.id + "][delete]", value: "1"})
                        }

                        if (this.shouldPriceShow(price) === false) {
                            return React.createElement("span", {key: 'empty' + price.id});
                        }

                        return React.createElement(PriceItem, React.__spread({data: price},  childProps, {key: price['id'], validate: this.props.validatePrices}))
                    }.bind(this))
                )
            );
        },
        shouldPriceShow: function (price) {
            if(price.variant_id) {
                return false;
            }
            var show = true;
            if (this.props['filter_customer_group_value'] && this.props['filter_customer_group_value'] !== '*' && this.props['filter_customer_group_value'] != price['customer_group_id']) {
                show = false;
            }
            if (this.props['filter_site_value'] && this.props['filter_site_value'] !== '*' && this.props['filter_site_value'] != price['site_id']) {
                show = false;
            }
            if (this.props['filter_currency_value'] && this.props['filter_currency_value'] !== '*' && this.props['filter_currency_value'] != price['currency_code']) {
                show = false;
            }
            return show;
        }
    });

    var PriceItem = React.createClass({displayName: "PriceItem",
        editable: true,
        render: function () {
            var price = this.props.data;
            this.editable = (this.props.editable_prices.indexOf(price['price_type']) != -1);
            var priceTypes = React.createElement("span", null, this.props.price_types[price['price_type']]);
            if(this.editable) {
                priceTypes =
                    React.createElement("select", {className: "to-select2 form-control priceUnique", 
                        name: this.getFieldName(price, 'price_type'), 
                        defaultValue: price['price_type'], ref: "price_type"}, 
                            _.map(this.props.price_types, function (pt, pk) {
                                return React.createElement("option", {key: pk, value: pk, disabled: pk == 'promo' ? 'disabled' : null}, pt)
                            })
                    );
            }

            var qty = React.createElement("input", {key: "qty", type: "hidden", name: this.getFieldName(price, "qty"), defaultValue: price['qty']});
            if (price['price_type'] === 'tier') {
                qty = React.createElement("input", {key: "qty", type: "text", className: "form-control priceUnique", name: this.getFieldName(price, "qty"), placeholder: Locale._("Amount"), 
                             defaultValue: price['qty'], onChange: this.props.validate, readOnly: this.editable ? null : 'readonly'});
            }

            var dateRange = React.createElement("span", {key: "sale_period"});
            if(price['price_type'] === 'sale') {
                dateRange = React.createElement("input", {ref: "sale_period", key: "sale_period", type: "text", className: "form-control", 
                    name: this.getFieldName(price, "sale_period"), placeholder: Locale._("Select sale dates"), 
                    defaultValue: price['sale_period'], readOnly: this.editable ? null : 'readonly'});
            }

            var priceFraction = React.createElement("span", {key: "price_fraction"});
            if(this.props.priceRelationOptions && this.props.priceRelationOptions[price['price_type']]) {
                var operation =
                    React.createElement("select", {name: this.getFieldName(price, 'operation'), defaultValue: price['operation'], 
                        ref: "operation", className: "to-select2"}, 
                        this.props.operationOptions.map(function (o) {
                            return React.createElement("option", {value: o.value, key: o.value}, o.label)
                        })
                    );
                var baseField = React.createElement("span", null);
                if(price['operation'] && price['operation'] !== "$$") {
                    baseField =
                        React.createElement("select", {key: "base_fields", name: this.getFieldName(price, 'base_field'), 
                            defaultValue: price['base_field'], className: "base_field to-select2"}, 
                            this.props.priceRelationOptions[price['price_type']].map(function (p) {
                                return React.createElement("option", {key: p.value, value: p.value}, p.label)
                            })
                        )
                }
                priceFraction = React.createElement("div", {style: divStyle, key: "price_fraction"}, 
                    [operation, baseField]
                );
            }

            return (
                React.createElement("div", {className: "form-group price-item"}, 
                    React.createElement("div", {style: divStyle}, 
                        React.createElement("a", {href: "#", className: "btn-remove", "data-id": price.id, 
                           id: "remove_price_btn_" + price.id}, 
                            React.createElement("span", {className: "icon-remove-sign"})
                        )
                    ), 
                    React.createElement("div", {style: divStyle}, 
                        priceTypes
                    ), 
                    React.createElement("div", {style: divStyle}, 
                        React.createElement("input", {type: "hidden", name: this.getFieldName(price, "product_id"), 
                               defaultValue: price['product_id']}), 
                        React.createElement("input", {type: "hidden", name: this.getFieldName(price, "customer_group_id"), 
                               defaultValue: price['customer_group_id'], className: "priceUnique"}), 
                        React.createElement("input", {type: "text", className: "form-control", readOnly: "readOnly", 
                               defaultValue: this.getCustomerGroupName(price['customer_group_id'])})
                    ), 
                    React.createElement("div", {style: divStyle}, 
                        React.createElement("input", {type: "hidden", name: this.getFieldName(price, "site_id"), 
                               defaultValue: price['site_id'], className: "priceUnique"}), 
                        React.createElement("input", {type: "text", className: "form-control", readOnly: "readOnly", 
                               defaultValue: this.getSiteName(price['site_id'])})
                    ), 
                    React.createElement("div", {style: divStyle}, 
                        React.createElement("input", {type: "hidden", name: this.getFieldName(price, "currency_code"), 
                               defaultValue: price['currency_code'], className: "priceUnique"}), 
                        React.createElement("input", {type: "text", className: "form-control", readOnly: "readOnly", 
                               defaultValue: this.getCurrencyName(price['currency_code'])})
                    ), 
                    React.createElement("div", {style: divStyle}, 
                        priceFraction
                    ), 
                    React.createElement("div", {style: divStyle}, 
                        React.createElement("input", {type: "text", className: "form-control", name: this.getFieldName(price, "price"), 
                               defaultValue: price['price'], readOnly: this.editable ? null: 'readonly'})
                    ), 
                    React.createElement("div", {style: divStyle}, 
                        [qty, dateRange]
                    )
                )
            );
        },
        componentDidMount: function () {
            this.initPrices();
        },
        componentDidUpdate: function () {
            if(this.props.data.operation && this.props.data.operation !== '$$') {
                $('select.base_field', this.getDOMNode()).select2({minimumResultsForSearch: 15, width: 'resolve'});
            }
        },
        initPrices: function () {
            $('select.to-select2', this.getDOMNode()).select2({minimumResultsForSearch: 15, width: 'resolve'});
            var self = this;
            if (this.editable) {
                $(this.refs['price_type'].getDOMNode()).on('change', function (e) {
                    e.stopPropagation();
                    var priceType = $(e.target).val();
                    var id = self.props.data.id;
                    self.props.updatePriceType(id, priceType);
                    self.props.validate();
                });
                $('a.btn-remove', this.getDOMNode()).on('click', function (e) {
                    e.preventDefault();
                    var id = $(this).data('id');
                    //console.log(id);
                    self.props.deletePrice(id);
                });

                if(this.props.data['price_type'] === 'sale'){
                    this.initDateInput();
                }

                var operation = this.refs['operation'];
                if (operation) {
                    $(operation.getDOMNode()).on('change', function (e) {
                        var operation = $(e.target).val();
                        var id = self.props.data.id;
                        self.props.updateOperation(id, operation);
                    })
                }
            }
        },
        initDateInput: function () {
            var data = this.props.data['sale_period'], s, e;
            var dateField = this.refs['sale_period'];
            if (!data) {
                var startDate = new Date();
                s = startDate.getFullYear() + '-' + (startDate.getMonth() + 1) + '-' + startDate.getDate();
            } else {
                var dates = data.split(" - ");
                s = dates[0];
                e = dates[1] || dates[0];
            }
            var $input = $(dateField.getDOMNode());
            var options = {
                format: 'YYYY-MM-DD',
                startDate: s
            };
            if (e) {
                options.endDate = e;
            }
            $input.daterangepicker(options);
            //todo set setStartDate and setEndDate
        },
        getFieldName: function (obj, field) {
            return "prices[productPrice][" + obj['id'] + "][" + field + "]";
        },
        _getPropOptionLabel: function (option, id) {
            if (null === id || undefined === id || false === id) {
                return Locale._("N/A");
            }
            if (this.props[option] && this.props[option][id]) {
                return this.props[option][id];
            }
            return id;
        },
        getCustomerGroupName: function (id) {
            return this._getPropOptionLabel('customer_groups', id);
        },
        getSiteName: function (id) {
            return this._getPropOptionLabel('sites', id);
        },
        getCurrencyName: function (id) {
            return this._getPropOptionLabel('currencies', id);
        }
    });

    var divStyle = {float: 'left', marginLeft: 15};
    var productPrice = {
        options: {
            title: Locale._("Product Prices")
        },
        newIdx: 0,
        init: function (options) {
            this.options = _.extend({}, this.options, options);

            var container = this.options.container;
            if(!container || !container.length) {
                console.log("Prices div container not found");
                return;
            }
            var no_filters = true;

            var checkAddAllowed = function (options) {
                var allowed = true;
                _.each(['filter_customer_group_value', 'filter_site_value', 'filter_currency_value'], function (value) {
                    allowed = (options[value] != '*');
                });
                if(allowed && options.prices_add_new && options.prices_add_new.length) {
                    options.prices_add_new.attr('disabled', false);
                }
            };
            _.each(['filter_customer_group', 'filter_site', 'filter_currency'], function (filter) {
                if (this.options[filter].length) {
                    this.options[filter + '_value'] = this.options[filter].val();
                    this.options[filter].on('change', function (e) {
                        e.preventDefault();
                        this.options[filter + '_value'] = $(e.target).val();
                        checkAddAllowed(this.options);
                        React.render(React.createElement(PricesApp, React.__spread({},  this.options)), this.options.container[0]);
                    }.bind(this));
                    no_filters = false;
                }
            }.bind(this));

            checkAddAllowed(this.options);

            if(this.options.prices_add_new && this.options.prices_add_new.length) {
                this.options.prices_add_new.on('click', function (e) {
                    e.preventDefault();
                    var newPrice = {
                        id: 'new_' + (this.newIdx++),
                        product_id: this.options.product_id,
                        price_type: 'tier',
                        customer_group_id: this.options.filter_customer_group_value || null,
                        site_id: this.options.filter_site_value || null,
                        currency_code: this.options.filter_currency_value || null,
                        price: 0.0,
                        qty: 1
                    };
                    if(!this.options.prices) {
                        this.options.prices = [];
                    }
                    this.options.prices.push(newPrice);
                    React.render(React.createElement(PricesApp, React.__spread({},  this.options)), this.options.container[0]);
                }.bind(this));

            }

            this.options.deletePrice = function (id) {
                if (!this.options['deleted']) {
                    this.options['deleted'] = {};
                }
                this.options['deleted'][id] = true;
                React.render(React.createElement(PricesApp, React.__spread({},  this.options)), this.options.container[0])
            }.bind(this);

            this.options.updatePriceType = function (price_id, price_type) {
                _.each(this.options.prices, function (price) {
                    if (price.id == price_id) {
                        price.price_type = price_type;
                    }
                });

                React.render(React.createElement(PricesApp, React.__spread({},  this.options)), this.options.container[0])
            }.bind(this);

            this.options.updateOperation = function (price_id, operation) {
                _.each(this.options.prices, function (price) {
                    if (price.id == price_id) {
                        price.operation = operation;
                    }
                });

                React.render(React.createElement(PricesApp, React.__spread({},  this.options)), this.options.container[0])
            }.bind(this);

            React.render(React.createElement(PricesApp, React.__spread({},  this.options)), container[0])
        }
    };
    return productPrice;
});
