
define([
  'jquery',
  'underscore',
], function ($, _) {

    var options = {
        cacheTtl: 0,
        sectionLoadUrl: ''
    }

    var getFromServer = function (sectionNames) {
        var parameters = {
            'requesting_page_url': window.location.href
        };
        var selector = '#product_addtocart_form [name="product"]';
        var productId = $(selector).val();

        if (productId) {
            parameters.data = {
                'product_id': productId
            };
        }

        if (_.isArray(sectionNames)) {
            parameters.sections = sectionNames.join(',');
        }

        return $.getJSON(options.sectionLoadUrl, parameters).fail(function (jqXHR) {
            console.log('Banner not loaded.');
        });
    }

    var mixin = {
      reload: function (sectionNames) {
        return getFromServer(sectionNames).done(function (sections) {
          _.each(sections, function (sectionData, sectionName) {
              this.set(sectionName, sectionData);
            }.bind(this));
        }.bind(this));
      }
    };

    return function (target) {
        return _.extend(target, mixin);
    };
});
