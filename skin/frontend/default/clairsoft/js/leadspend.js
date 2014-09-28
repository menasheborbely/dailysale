var $j = jQuery.noConflict();

(function(e, t, n, r) {
    function o(t, n) {
        this.element = t;
        this.resultElement = null;
        this.options = e.extend({}, s, n);
        this._defaults = s;
        this._name = i;
        this.apiUrl = "https://secondary.api.leadspend.com/v2/validity/";
        this._jsonpValidateEmail = function(t) {
            e.getJSON(this.apiUrl + encodeURIComponent(t) + "?timeout=" + this.options.timeout + "&callback=?", null).done(e.proxy(this._jsonpValidateEmailDone, this)).fail(e.proxy(this._jsonpValidateEmailFail, this));
        };
        this._jsonpValidateEmailDone = function(t, n, r) {
            if (this.options.debug) {
                console.log(t);
            }
            this._setResultPending(false);
            e(this.resultElement).val(t.result);
        };
        this._jsonpValidateEmailFail = function(t, n, r) {
            if (this.options.debug) {
                console.log("LeadSpend API Call Failed.  Logging jqXHR, textStatus, and errorThrown:");
                console.log(t);
                console.log(n);
                console.log(r);
            }
            this._setResultPending(false);
            e(this.resultElement).val("error");
        };
        this._createResultElement = function() {
            elementID = e(this.element).attr("id");
            elementName = e(this.element).attr("name");
            if (elementID) {
                resultElementID = elementID + this.options.resultInputSuffix;
            } else {
                resultElementID = "";
            } if (elementName) {
                resultElementName = elementName + this.options.resultInputSuffix;
            } else {
                resultElementName = "";
            }
            resultElementClass = "leadSpendEmail" + this.options.resultInputSuffix;
            resultElementHtml = '<input class="' + resultElementClass + '" id="' + resultElementID + '" name="' + resultElementName + '">';
            this.resultElement = e(resultElementHtml);
            this.resultElement.hide();
            e(this.element).after(this.resultElement);
        };
        this._setResultPending = function(t) {
            if (t) {
                this.resultPending = true;
                e(this.resultElement).val("pending");
            } else {
                this.resultPending = false;
            }
        };
        this._isResultPending = function() {
            return this.resultPending;
        };
        this._setResultAddress = function(e) {
            this.resultAddress = e;
        };
        this._getResultAddress = function() {
            return this.resultAddress;
        };
        this.validateEmailInput = function() {
            emailAddress = e(this.element).val();
            if (emailAddress.indexOf("@") != -1 && emailAddress.indexOf(".") != -1 && emailAddress.indexOf("@") < emailAddress.lastIndexOf(".")) {
                if (!this.resultElement) {
                    this._createResultElement();
                }
                if (emailAddress != this._getResultAddress()) {
                    this._setResultPending(true);
                    this._setResultAddress(emailAddress);
                    this._jsonpValidateEmail(emailAddress);
                }
            }
        };
        this.init();
    }
    var i = "leadSpendEmail",
        s = {
            timeout: 5,
            resultInputSuffix: "-result",
            debug: false
        };
    o.prototype.init = function() {
        return e(this.element).on("focusout", e.proxy(this.validateEmailInput, this));
    };
    e.fn[i] = function(t) {
        return this.each(function() {
            if (!e.data(this, "plugin_" + i)) {
                e.data(this, "plugin_" + i, new o(this, t));
            } else if (t) {
                e.data(this, "plugin_" + i).options = e.extend({}, e.data(this, "plugin_" + i).options, t);
            }
        });
    };
})(jQuery, window, document);
$j(document).ready(function() {
    $j(".leadSpendEmail").leadSpendEmail();
});