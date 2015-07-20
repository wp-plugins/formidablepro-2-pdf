jQuery(document).ready ($) ->

  $('#use-second-layout').change ->
    $('.hidden-use-second').toggle()
    true

  container = $ '#wpbody-content .parent ._first'

  div = $ '<div />'
  div
    .addClass 'formidable-shortcode-container'
    .html 'Shortcode for Download Link or Button&nbsp;'
    .insertAfter container
    .hide()

  span = $ '<input />'
  span
    .appendTo div

  activated = parseInt $('#frm-bg').data('activated')

  if activated
    div.append 'Shortcode for Automatic Download'

  span2 = $ '<input />'

  if activated
    span2
      .appendTo div

  span3 = $ '<span />'
  if activated
    span3
      .appendTo div

  div.find('input')
    #.prop "readonly", true
    .focus ->
      this.select()
      true 

  main_form = container.find "form"
  oldData = false
  setInterval ->
    data = main_form.serialize()
    if oldData != data
      setShortcode()
    oldData = data
  , 10

  setShortcode = ->
    form = $('#wpfx_form').val()
    form2 = $('#wpfx_form2').val()
    dataset = $('#wpfx_dataset').val()
    dataset2 = $('#wpfx_dataset2').val()
    layout = $('#wpfx_layout').val()
    layout2 = $('#wpfx_layout2').val()
    dataset ||= '-3'
    dataset2 ||= '-3'
    dataset = '-3' if dataset == "Loading datasets..."
    dataset2 = '-3' if dataset2 == "Loading datasets..."
    datasetOrig = ""
    datasetOrig2 = ""
    if dataset != '-3'
      datasetOrig = dataset
      dataset = " dataset=\"#{dataset}\""
    else
      dataset = ""
    if dataset2 != '-3'
      datasetOrig2 = dataset2
      dataset2 = " dataset2=\"#{dataset2}\""
    else
      dataset2 = ""

    second = ""
    secondUrl = ""
    if $('#use-second-layout').is ':checked'
      second = " form2=\"#{form2}\"#{dataset2} layout2=\"#{layout2}\""
      secondUrl = "&form2=#{form2}&dataset2=#{datasetOrig2}&layout2=#{layout2}"

    span.val "[formidable-download form=\"#{form}\"#{dataset} layout=\"#{layout}\"#{second}]"
    span2.val "[formidable-download form=\"#{form}\"#{dataset} layout=\"#{layout}\"#{second} download=\"auto\"]"
    span3.html ''



    if dataset.length
      span3.html "<a href='#{window.ajaxurl}?action=wpfx_generate&form=#{form}&layout=#{layout}&dataset=#{datasetOrig}#{secondUrl}&inline=1' target='_blank' class='button'>Preview</a>"
    $('#main-export-btn').attr 'href', "#{window.ajaxurl}?action=wpfx_generate&form=#{form}&layout=#{layout}&dataset=#{datasetOrig}#{secondUrl}&inline=1"

  container.find('select, input[type="checkbox"]').change setShortcode

  setInterval ->
    bottom = $ '.layout_builder'
    if bottom.is ':visible'
      #div.css 'width', bottom.outerWidth()
      div.show()
      #setShortcode()
  , 10


  $(document).on 'change', '#wpfx_layout_form table table select.with-preview', ->
    select = $ this
    select.parent().find('.custom-preview-img').remove()
    img = $ '<img />'
    img.addClass 'custom-preview-img'
    img.insertAfter select
    base = select.data "base"
    src = select.find('option:selected').data "src"
    src = "admin-ajax.php?page=fpdf&action=wpfx_preview_pdf&file=" + encodeURIComponent( $('#wpfx_clfile').val() ) + "&field=" + encodeURIComponent( select.val() ) + "&form=" + encodeURIComponent( $('#wpfx_clform').val() ) + "&ext=.png"
    #if base.length and src.length
      #img.attr 'src', "#{base}#{src}"
    #else
      #img.remove()
    img.wrap "<a href='#{src}&TB_iframe=true' target='_blank' class='thickbox' />"
    img.load ->
      img.css 'max-width', 'initial'
      a = img.parent()
      href = a.attr 'href'
      href += "&width=" + ( img.width() - ( 626 - 597 ) ) + "&height=" + (img.height() - ( 200 - 188  ))
      a.attr 'href', href
      img.css 'max-width', ''
    img.attr 'src', src

  $(document).on 'click', '.upl-new-pdf', ->
    overlay = $ '<div />'
    overlay
      .addClass 'fpropdf-overlay'
      .appendTo 'body'
    form = $ '<form method="post" enctype="multipart/form-data" />'
    form
      .append 'Select a file: <input type="hidden" name="action" value="upload-pdf-file" /> <input accept="application/pdf" required="required" type="file" name="upload-pdf" />'
      .append '<input type="submit" class="button-primary" value="Upload" /> &nbsp; <a href="#" class="button cancel">Cancel</a>'
      .appendTo overlay
      .submit ->
        btn = $(this).find('.button-primary')
        setTimeout ->
          btn
            .html 'Please wait...'
            .val 'Please wait...'
            .prop 'disabled', true
        , 50
        true
      .wrapInner '<div />'
    form.find('.cancel').click ->
      overlay.remove()
      false
    false

  $(document).on 'click', 'input.remove-pdf', ->
    option = $('#wpfx_clfile option:selected')
    btn = $ this
    fnameEncoded = option.attr 'value'
    html = btn.val()
    fname = option.text()
    return false unless confirm("Are you sure you want to delete #{fname} ?")
    return false unless confirm("This action cannot be undone. The file #{fname} will be lost. Do you really want to delete it?")
    #console.log 'delete', fnameEncoded
    btn
      .prop 'disabled', true
      .html 'Please wait...'
    $.ajax
      url: window.ajaxurl
      data:
        file: fnameEncoded
        action: 'fpropdf_remove_pdf'
      method: 'POST'
      success: ->
        option.remove()
        alert "#{fname} has been removed."
        btn
          .prop 'disabled', false
          .val html
    false

  $(document).on 'click', '.fpropdf-activate', ->
    overlay = $ '<div />'
    overlay
      .addClass 'fpropdf-overlay'
      .appendTo 'body'
    form = $ '<form method="post" enctype="multipart/form-data" />'
    form
      .append 'Your activation code: <br /><br /> <input type="hidden" name="action" value="activate-fpropdf" /> <input type="text" name="activation-code" value="" required="required" placeholder="Paste your activation code here..." style="width: 300px; text-align: center;" /><br /><br />'
      .append '<input type="submit" class="button-primary" value="Activate" /> &nbsp; <a href="#" class="button cancel">Cancel</a>'
      .append '<br /><br /><a href="http://formidablepro2pdf.com/" target="_blank">How can I get an activation code?</a>'
      .appendTo overlay
      .submit ->
        btn = $(this).find('.button-primary')
        setTimeout ->
          btn
            .html 'Please wait...'
            .val 'Please wait...'
            .prop 'disabled', true
        , 50
        true
      .wrapInner '<div />'
    form.find('.cancel').click ->
      overlay.remove()
      false
    form.find('input[type="text"]').focus()

    false
