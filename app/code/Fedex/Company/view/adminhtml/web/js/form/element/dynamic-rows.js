define(
    [
        'Magento_Ui/js/dynamic-rows/dynamic-rows'
    ],
    function (Component) {
        return Component.extend({

            defaults: {pageSize: 1},
            processingAddChild: function (ctx, index, prop) {
                this.bubble('addChild', false);

                if (!(this._elems.length > this.pageSize)) {
                    if (this.relatedData.length && this.relatedData.length % this.pageSize === 0) {
                        this.pages(this.pages() + 1);
                        this.nextPage();
                    } else if (~~this.currentPage() !== this.pages()) {
                        this.currentPage(this.pages());
                    }

                    this.addChild(ctx, index, prop);
                }
            }
        });
    }
);
