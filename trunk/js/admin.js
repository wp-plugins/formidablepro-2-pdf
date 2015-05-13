// Generated by CoffeeScript 1.9.0
(function() {
  jQuery(document).ready(function($) {
    var activated, container, div, main_form, oldData, setShortcode, span, span2, span3;
    $('#use-second-layout').change(function() {
      $('.hidden-use-second').toggle();
      return true;
    });
    container = $('#wpbody-content .parent ._first');
    div = $('<div />');
    div.addClass('formidable-shortcode-container').html('Shortcode for Download Link or Button&nbsp;').insertAfter(container).hide();
    span = $('<input />');
    span.appendTo(div);
    activated = parseInt($('#frm-bg').data('activated'));
    if (activated) {
      div.append('Shortcode for Automatic Download');
    }
    span2 = $('<input />');
    if (activated) {
      span2.appendTo(div);
    }
    span3 = $('<span />');
    if (activated) {
      span3.appendTo(div);
    }
    div.find('input').focus(function() {
      this.select();
      return true;
    });
    main_form = container.find("form");
    oldData = false;
    setInterval(function() {
      var data;
      data = main_form.serialize();
      if (oldData !== data) {
        setShortcode();
      }
      return oldData = data;
    }, 10);
    setShortcode = function() {
      var dataset, dataset2, datasetOrig, datasetOrig2, form, form2, layout, layout2, second, secondUrl;
      form = $('#wpfx_form').val();
      form2 = $('#wpfx_form2').val();
      dataset = $('#wpfx_dataset').val();
      dataset2 = $('#wpfx_dataset2').val();
      layout = $('#wpfx_layout').val();
      layout2 = $('#wpfx_layout2').val();
      dataset || (dataset = '-3');
      dataset2 || (dataset2 = '-3');
      if (dataset === "Loading datasets...") {
        dataset = '-3';
      }
      if (dataset2 === "Loading datasets...") {
        dataset2 = '-3';
      }
      datasetOrig = "";
      datasetOrig2 = "";
      if (dataset !== '-3') {
        datasetOrig = dataset;
        dataset = " dataset=\"" + dataset + "\"";
      } else {
        dataset = "";
      }
      if (dataset2 !== '-3') {
        datasetOrig2 = dataset2;
        dataset2 = " dataset2=\"" + dataset2 + "\"";
      } else {
        dataset2 = "";
      }
      second = "";
      secondUrl = "";
      if ($('#use-second-layout').is(':checked')) {
        second = " form2=\"" + form2 + "\"" + dataset2 + " layout2=\"" + layout2 + "\"";
        secondUrl = "&form2=" + form2 + "&dataset2=" + datasetOrig2 + "&layout2=" + layout2;
      }
      span.val("[formidable-download form=\"" + form + "\"" + dataset + " layout=\"" + layout + "\"" + second + "]");
      span2.val("[formidable-download form=\"" + form + "\"" + dataset + " layout=\"" + layout + "\"" + second + " download=\"auto\"]");
      span3.html('');
      if (dataset.length) {
        span3.html("<a href='" + window.ajaxurl + "?action=wpfx_generate&form=" + form + "&layout=" + layout + "&dataset=" + datasetOrig + secondUrl + "&inline=1' target='_blank' class='button'>Preview</a>");
      }
      return $('#main-export-btn').attr('href', window.ajaxurl + "?action=wpfx_generate&form=" + form + "&layout=" + layout + "&dataset=" + datasetOrig + secondUrl + "&inline=1");
    };
    container.find('select, input[type="checkbox"]').change(setShortcode);
    setInterval(function() {
      var bottom;
      bottom = $('.layout_builder');
      if (bottom.is(':visible')) {
        return div.show();
      }
    }, 10);
    $(document).on('change', '#wpfx_layout_form table table select.with-preview', function() {
      var base, img, select, src;
      select = $(this);
      select.parent().find('.custom-preview-img').remove();
      img = $('<img />');
      img.addClass('custom-preview-img');
      img.insertAfter(select);
      base = select.data("base");
      src = select.find('option:selected').data("src");
      src = "admin-ajax.php?page=fpdf&action=wpfx_preview_pdf&file=" + encodeURIComponent($('#wpfx_clfile').val()) + "&field=" + encodeURIComponent(select.val()) + "&form=" + encodeURIComponent($('#wpfx_clform').val());
      img.attr('src', src);
      return img.wrap("<a href='" + src + "' target='_blank' />");
    });
    $(document).on('click', '.upl-new-pdf', function() {
      var form, overlay;
      overlay = $('<div />');
      overlay.addClass('fpropdf-overlay').appendTo('body');
      form = $('<form method="post" enctype="multipart/form-data" />');
      form.append('Select a file: <input type="hidden" name="action" value="upload-pdf-file" /> <input accept="application/pdf" required="required" type="file" name="upload-pdf" />').append('<input type="submit" class="button-primary" value="Upload" /> &nbsp; <a href="#" class="button cancel">Cancel</a>').appendTo(overlay).submit(function() {
        var btn;
        btn = $(this).find('.button-primary');
        setTimeout(function() {
          return btn.html('Please wait...').val('Please wait...').prop('disabled', true);
        }, 50);
        return true;
      }).wrapInner('<div />');
      form.find('.cancel').click(function() {
        overlay.remove();
        return false;
      });
      return false;
    });
    $(document).on('click', 'input.remove-pdf', function() {
      var btn, fname, fnameEncoded, html, option;
      option = $('#wpfx_clfile option:selected');
      btn = $(this);
      fnameEncoded = option.attr('value');
      html = btn.val();
      fname = option.text();
      if (!confirm("Are you sure you want to delete " + fname + " ?")) {
        return false;
      }
      if (!confirm("This action cannot be undone. The file " + fname + " will be lost. Do you really want to delete it?")) {
        return false;
      }
      btn.prop('disabled', true).html('Please wait...');
      $.ajax({
        url: window.ajaxurl,
        data: {
          file: fnameEncoded,
          action: 'fpropdf_remove_pdf'
        },
        method: 'POST',
        success: function() {
          option.remove();
          alert(fname + " has been removed.");
          return btn.prop('disabled', false).val(html);
        }
      });
      return false;
    });
    return $(document).on('click', '.fpropdf-activate', function() {
      var form, overlay;
      overlay = $('<div />');
      overlay.addClass('fpropdf-overlay').appendTo('body');
      form = $('<form method="post" enctype="multipart/form-data" />');
      form.append('Your activation code: <br /><br /> <input type="hidden" name="action" value="activate-fpropdf" /> <input type="text" name="activation-code" value="" required="required" placeholder="Paste your activation code here..." style="width: 300px; text-align: center;" /><br /><br />').append('<input type="submit" class="button-primary" value="Activate" /> &nbsp; <a href="#" class="button cancel">Cancel</a>').append('<br /><br /><a href="http://formidablepro2pdf.com/" target="_blank">How can I get an activation code?</a>').appendTo(overlay).submit(function() {
        var btn;
        btn = $(this).find('.button-primary');
        setTimeout(function() {
          return btn.html('Please wait...').val('Please wait...').prop('disabled', true);
        }, 50);
        return true;
      }).wrapInner('<div />');
      form.find('.cancel').click(function() {
        overlay.remove();
        return false;
      });
      form.find('input[type="text"]').focus();
      return false;
    });
  });

}).call(this);
