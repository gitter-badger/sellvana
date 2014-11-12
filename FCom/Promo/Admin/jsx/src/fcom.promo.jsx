/** @jsx React.DOM */

define(['react', 'jquery', 'jsx!griddle', 'jsx!fcom.components', 'select2', 'bootstrap', 'fcom.locale'], function (React, $, Griddle, Components) {
    var Locale = require('fcom.locale');
    var SingleCoupon = React.createClass({
        render: function () {
            return (
                <div className="single-coupon">
                    <Components.ControlLabel input_id={this.props.id}>
                        {this.props.labelText}<Components.HelpIcon id={"help-" + this.props.id} content={this.props.helpText}/>
                    </Components.ControlLabel>
                    <div className="col-md-5">
                        <input id={this.props.id} ref={this.props.name} className="form-control"/>
                        <span className="help-block">{this.props.helpText}</span>
                    </div>
                </div>
            );
        },
        getDefaultProps: function () {
            // component default properties
            return {
                id: "model-use_coupon_code_single",
                name: "use_coupon_code_single",
                helpText: Locale._("(Leave empty for auto-generate)"),
                labelText: Locale._("Coupon Code")
            };
        },
        getInitialState: function () {
            // component default properties
            return {
                value: ''
            };
        }
    });

    var MultiCoupon = React.createClass({
        render: function () {
            var showModal = <Components.Modal ref="showModal" onConfirm={this.handleShowConfirm}
                onCancel={this.closeShowModal} url={this.props.showCouponsurl} title="Coupon grid"/>;
            var generateModal = <Components.Modal ref="generateModal" onConfirm={this.handleGenerateConfirm}
                onCancel={this.closeGenerateModal} url={this.props.generateCouponsurl} title="Generate coupons"/>;
            var importModal = <Components.Modal ref="importModal" onConfirm={this.handleImportConfirm}
                onCancel={this.closeImportModal} url={this.props.importCouponsurl} title="Import coupons"/>;
            var style = {marginBottom: 15};
            return (
                <div className="multi-coupon btn-group col-md-offset-2" style={style}>
                    <Components.Button onClick={this.showCodes} className="btn-primary" type="button">{this.props.buttonViewLabel}</Components.Button>
                    <Components.Button onClick={this.generateCodes} className="btn-primary" type="button">{this.props.buttonGenerateLabel}</Components.Button>
                    <Components.Button onClick={this.importCodes} className="btn-primary" type="button">{this.props.buttonImportLabel}</Components.Button>
                    {showModal}
                    {generateModal}
                    {importModal}
                </div>
            );
        },
        closeShowModal: function () {
            this.refs.showModal.close();
        },
        closeGenerateModal: function () {
            this.refs.generateModal.close();
        },
        closeImportModal: function () {
            this.refs.importModal.close();
        },
        getDefaultProps: function () {
            // component default properties
            return {
                buttonViewLabel: Locale._("View (100) codes"),
                buttonGenerateLabel: Locale._("Generate New Codes"),
                buttonImportLabel: Locale._("Import Existing Codes"),
                showCouponsUrl:"",
                generateCouponsUrl:"",
                importCouponsUrl:""
            }
        },
        loadModalContent: function ($modalBody, url, success) {
            if ($modalBody.length > 0 && $modalBody.data('content-loaded') == undefined) {
                $.get(url).done(function (result) {
                    if (result.hasOwnProperty('html')) {
                        $modalBody.html(result.html);
                        //$modalBody.data('content-loaded', true)
                        if(typeof success == 'function'){
                            success($modalBody);
                        }
                    }
                }).fail(function(result){
                    var jsonResult = result.responseJSON;
                    if (jsonResult.hasOwnProperty('html')) {
                        $modalBody.html(jsonResult.html);
                    }
                });
            }
        },
        showCodes: function () {
            // component default properties
            console.log("showCodes");
            this.refs.showModal.open();
            var $modalBody = $('.modal-body', this.refs.showModal.getDOMNode());
            this.loadModalContent($modalBody, this.props.showCouponsUrl)
        },
        generateCodes: function () {
            // component default properties
            console.log("generateCodes");
            this.refs.generateModal.open();
            var $modalBody = $('.modal-body', this.refs.generateModal.getDOMNode());
            this.loadModalContent($modalBody, this.props.generateCouponsUrl, this.postGenerate);
        },
        postGenerate: function($el){
            var $form = $el.find('form');
            var $button = $form.find('button.btn-post');
            var $codeLength = $form.find('input[name="model[code_length]"]');
            var $codePattern = $form.find('input[name="model[code_pattern]"]');
            var url = this.props.generateCouponsUrl;
            if($.trim($codePattern.val()) == ''){ // code length should be settable only if no pattern is provided
                $codeLength.prop('disabled', false);
            }
            $codePattern.change(function () {
                var val = $.trim($codePattern.val());
                if (val == '') {
                    $codeLength.prop('disabled', false);
                } else {
                    $codeLength.prop('disabled', true);
                    $codePattern.val(val);
                }
            });
            $button.click(function (e) {
                e.preventDefault();
                var data = {};
                $form.find('input').each(function(){
                    var $self = $(this);
                    var name = $self.attr('name');
                    data[name] = $self.val();
                });
                // show indication that something happens?
                $.post(url, data)
                    .done(function (result) {
                        var status = result.status;
                        var message = result.message;
                        $el.append($('<pre>').addClass((status == 'warning')?'warning':'success').text(message));
                    })
                    .always(function (r) {
                        // hide notification
                        console.log(r);
                    });
            });
        },
        importCodes: function () {
            // component default properties
            console.log("importCodes");
            this.refs.importModal.open();
            var $modalBody = $('.modal-body', this.refs.importModal.getDOMNode());
            this.loadModalContent($modalBody, this.props.importCouponsUrl);
        }
    });

    var UsesBlock = React.createClass({
        render: function () {
            return (
                <div className="uses-block" style={{clear: 'both'}}>
                    <Components.ControlLabel input_id={this.props.idUpc}>
                        {this.props.labelUpc}<Components.HelpIcon id={"help-" + this.props.idUpc} content={this.props.helpTextUpc}/>
                    </Components.ControlLabel>
                    <div className="col-md-3">
                        <input type="text" id={this.props.idUpc} ref="uses_pc" className="form-control"
                            value={this.state.valueUpc}/>
                    </div>

                    <Components.ControlLabel input_id={this.props.idUt}>
                        {this.props.labelUt}<Components.HelpIcon id={"help-" + this.props.idUt} content={this.props.helpTextUt}/>
                    </Components.ControlLabel>

                    <div className="col-md-3">
                        <input type="text" id={this.props.idUt} ref="uses_pc" className="form-control"
                            value={this.state.valueUt}/>
                    </div>
                </div>
            );
        },
        getDefaultProps: function () {
            // component default properties
            return {
                labelUpc: Locale._("Uses Per Customer"),
                labelUt: Locale._("Total Uses"),
                idUpc: "coupon_uses_per_customer",
                idUt: "coupon_uses_total",
                helpTextUpc: Locale._("How many times a user can use a coupon?"),
                helpTextUt: Locale._("How many total times a coupon can be used?")
            };
        },
        getInitialState: function () {
            // component default properties
            return {
                valueUpc: '',
                valueUt: ''
            };
        }, componentWillMount: function () {
            if(this.props.options.valueUpc) {
                this.setState({valueUpc: this.props.options.valueUpc});
            }
            if(this.props.options.valueUt) {
                this.setState({valueUt: this.props.options.valueUt});
            }
        }
    });

    var CouponApp = React.createClass({
        displayName: 'CouponApp',
        render: function () {
            //noinspection BadExpressionStatementJS
            var child = "";

            if (this.state.mode == 1) {
                child = [<SingleCoupon key="single-coupon" options={this.props.options}/>,
                    <UsesBlock options={this.props.options} key="uses-block"/>];
            } else if(this.state.mode == 2) {
                var showCouponsUrl = this.props.options.showCouponsUrl ||'',
                    generateCouponsUrl = this.props.options.generateCouponsUrl ||'',
                    importCouponsUrl = this.props.options.importCouponsUrl ||'';
                child = [<MultiCoupon key="multi-coupon" options={this.props.options} importCouponsUrl={importCouponsUrl}
                    generateCouponsUrl={generateCouponsUrl} showCouponsUrl={showCouponsUrl}/>,
                                        <UsesBlock options={this.props.options} key="uses-block"/>]
            }
            return (
                <div className="form-group">
                    <div className="coupon-group">
                        {child}
                    </div>
                </div>
            );
        },
        getInitialState: function () {
            return {mode: 0};
        },
        componentWillReceiveProps: function (nextProps) {
            this.setState({mode: nextProps.mode});
        },
        componentWillMount: function () {
            this.setState({mode: this.props.mode});
        }
    });

    var Promo = {
        createButton: function () {
            React.render(<Button label="Hello button"/>, document.getElementById('testbed'));
        },
        createGrid: function() {
            React.render(<Griddle/>, document.getElementById('testbed'));
        },
        init: function (options) {
            var couponSelectId = options.coupon_select_id || "model-use_coupon";
            var $couponSelector = $('#' + couponSelectId);
            if ($couponSelector.length == 0) {
                console.log("Use coupon dropdown not found");
                return;
            }
            var containerID = options.coupon_container_id || "coupon-options";
            var $element = $("#" + containerID);
            var selected = $couponSelector.val();
            if(selected != 0) {
                React.render(<CouponApp mode={parseInt(selected)} options={options}/>, $element[0]);
            }

            $couponSelector.on('change', function () {
                selected = $couponSelector.val();
                React.render(<CouponApp mode={parseInt(selected)} options={options}/>, $element[0]);
            });
        }
    };
    return Promo;
});
