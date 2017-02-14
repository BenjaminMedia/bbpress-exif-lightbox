jQuery(document).ready(function () {
    // find any image placed inside .bbp-topic-content or bbp-reply-content

    format_exp_time = function(time){
        if (time<1){
            tmp = (1/time);
            tmp = Math.round(tmp * 1000) / 1000;   //make sure time=0.0666667 is turning into 1/15 and not 1/14.999999
            return '1/' + tmp;
        }

        return '"' + time;
    }

    getItems = function () {
        var items = [];
        itemCounter = 0;
        jQuery('.bbp-reply-content .lightbox, .bbp-topic-content .lightbox').each(function (index) {
            //get all images that wants to be displayed in a lightbox

            var imageAnchor = jQuery(this);
            var exifData = imageAnchor.attr('data-exif');
            fullSizeImage = imageAnchor.attr('href'),
                dimensions = imageAnchor.attr('data-dimensions').split('x'),
                width = dimensions[0],
                height = dimensions[1];

            var modifiedExifData = JSON.parse(exifData);

            var exifHtml = '';

            jQuery.each(modifiedExifData.image_meta, function(key, value){
                if(key == 'aperture' && value != '0'){
                    exifHtml += '<strong>Aperture:</strong>&nbsp;f/' + value;
                }

                if(key == 'camera' && value != ''){
                    exifHtml += ' | <strong>Camera:</strong>&nbsp;' + value;
                }

                if (key == 'shutter_speed' && value != '0') {
                    exifHtml += ' | <strong>Exposure time:</strong>&nbsp;' + format_exp_time(value);
                }

                if (key == 'focal_length' && value != '0') {
                    exifHtml += ' | <strong>Focal length:</strong>&nbsp;' + value + ' mm';
                }

                if (key == 'iso' && value != '0') {
                    exifHtml += ' | <strong>ISO:</strong>&nbsp;' + value;
                }
            });

            var item = {
                src: fullSizeImage,
                w: width,
                h: height,
                index: itemCounter,
                title: exifHtml
            }

            jQuery(this).attr('data-index', itemCounter);

            items.push(item);
            itemCounter++;
        });
        return items;
    }

    var items = getItems();

    var pswp = jQuery('.pswp')[0];

    jQuery('.bbp-reply-content .lightbox, .bbp-topic-content .lightbox').click(function (event) {
        event.preventDefault();

        var itemCount = parseInt(jQuery(this).attr("data-index"), 10);
        var options = {
            index: itemCount,
            bgOpacity: 0.7,
            showHideOpacity: true
        }

        // Initialize PhotoSwipe
        var lightBox = new PhotoSwipe(pswp, PhotoSwipeUI_Default, items, options);
        lightBox.init();

        return false;
    });
});
