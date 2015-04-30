function recalcRadio(z)
{
    jQuery('.radioname').each(function(i, e)
    {
        jQuery(e).val('name_' + i);

        if(z != undefined && i == z) jQuery(e).prop('checked', true);
    });
}

function adjustLayout()
{
    var f = jQuery('#wpfx_form :selected').val();

    switch(f)
    {
        case '218da3':
            jQuery("#wpfx_layout [value='2']").attr("selected", "selected");
            jQuery('.layout_builder').hide();
            break;

        case '9wfy4z':
            jQuery("#wpfx_layout [value='1']").attr("selected", "selected");
            jQuery('.layout_builder').hide();
            break;

        default:
            jQuery("#wpfx_layout [value='3']").attr("selected", "selected");
            jQuery('.layout_builder').show();
            break;
    }
}

function onLayoutChange()
{
    if(jQuery('#wpfx_layout :selected').val() > 2 )
    {
        jQuery('.layout_builder').hide();
        jQuery('#loader').show();

        jQuery.ajax({dataType: "json", url: ajaxurl, type: "POST",
          timeout: 1000000,
                    data:
                    {
                        'wpfx_layout' : jQuery('#wpfx_layout :selected').val(),
                                               'action'      : 'wpfx_get_layout'
                    }, success: function(data)
                    {  
    if(jQuery('#wpfx_layout :selected').val() == "3") 
    {
      jQuery('#clnewmap, [type="reset"], #remvcl, .cltable').hide();
      jQuery('#clbody').html('<tr><td><input name="clname" type="radio" class="radioname" value="name_0"></td><td><input name="clfrom[]"></td><td id="maps">»»</td><td><input name="clto[]"></td><td id="delete">×</td></tr>');
      jQuery('#savecl').val('Create Field Map');

    }
    else
    {
      jQuery('#clnewmap, [type="reset"], #remvcl, .cltable').show();
      jQuery('#clbody').html('');
      jQuery('#savecl').val('Save Field Map');
    }
                        jQuery('#loader').hide();
                        jQuery('.layout_builder').show();
                        jQuery('#wpfx_clname').val(data.name);

                        jQuery("#wpfx_clfile [value = '" + data.file + "']").attr("selected", "selected");
                        jQuery("#wpfx_layoutvis [value = '" + data.visible + "']").attr("selected", "selected");
                        jQuery("#wpfx_clform [value = '" + data.form + "']").attr("selected", "selected");

                        jQuery('#clbody').empty();

                        jQuery.each(data.data, function(i, item)
                        {
                            //var row = jQuery("<tr><td><input name = 'clname' type = 'radio' class = 'radioname' /></td><td><input name = 'clfrom[]' value = '" + i + "' /></td><td id = 'maps'>&raquo;&raquo;</td><td><input name = 'clto[]' value = '" + item + "' /></td><td id = 'delete' title='Delete this row'>&times;</td>").on('click', '#delete', function() {  jQuery(this).closest ('tr').remove (); } );
                            //

                            var row;
                            var select2 = "<input name = 'clto[]' value = '" + item + "' />";
                            if ( data.fields2 )
                            {

                              options = "";
                              jQuery.each(data.fields2, function (j, field) {
                                selected = "";
                                if ( item+'' == field+'' )
                                  selected = ' selected="selected"';
                                options += '<option value="'+field+'"'+selected+' data-src="'+data.images[ field ]+'">'+field+'</option>';
                              });
                              
                              select2 = "<select class='with-preview' name='clto[]' data-base='"+data.imagesBase+"'>"+options+"</select>";

                            }

                            var select1 = "<input name = 'clfrom[]' value = '" + i + "' />";
                            if ( data.fields1 )
                            {

                              options = "";
                              jQuery.each(data.fields1, function (key, field) {
                                selected = "";
                                if ( i+'' == key+'' )
                                  selected = ' selected="selected"';
                                options += '<option value="'+key+'"'+selected+'>'+field+'</option>';
                              });
                              
                              select1 = "<select name='clfrom[]'>"+options+"</select>";

                            }

                            var formats2 = {
                              none: '(no formatting)',
                              tel: 'Telephone',
                              date: 'Date MM/DD/YY',
                              curDate: 'Current Date MM/DD/YY',
                              capitalize: 'Capitalize',
                              returnToComma: 'Carriage return to comma'
                            };
                            var options2 = "";
                            for ( var key2 in formats2 )
                            {
                              var selected2 = "";
                              if ( data.formats[item+''] == key2+'' )
                                selected2 = ' selected="selected"';
                              options2 += '<option value="'+key2+'"'+selected2+'>'+formats2[key2]+'</option>';
                            }
                            format2 = "<td><select name='format[]'>"+options2+"</select></td>";

                            if ( ! parseInt( jQuery('#clbody').data('activated') ) )
                              format2 = "";

                            row = jQuery("<tr><td><input name = 'clname' type = 'radio' class = 'radioname' /></td><td>"+select1+"</td><td id = 'maps'>&raquo;&raquo;</td><td>"+select2+"</td>"+format2+"<td id = 'delete' title='Delete this row'>&times;</td>").on('click', '#delete', function() {  jQuery(this).closest ('tr').remove (); } );

                            jQuery('#clbody').append(row);

                            row.find('select').trigger('change');
                        });

                        recalcRadio(data.index);
                    }
        });


    }
    else
    {
        // if not custom layout, show form
        jQuery('.layout_builder').hide();

    }
}

function adjustDataset()
{

  jQuery('#wpfx_dataset').html('<option>Loading datasets...</option>').prop('disabled', true);

    jQuery.ajax({dataType: "json", url: ajaxurl, type: "POST",
                data:
                {
                    'wpfx_form_key' : jQuery('#wpfx_form :selected').val(),
                                             'action'        : 'wpfx_get_dataset'
                }, success: function(data)
                {
                    jQuery('#wpfx_dataset').find('option').remove();
                    jQuery('#wpfx_dataset').prop('disabled', false);

                    jQuery.each(data, function(i,item)
                    {
                        jQuery('#wpfx_dataset').append("<option value = '" + item.id + "'>" + item.date + "</option>");
                        if ( item.date == "This form does not contain datasets" )
                          jQuery('#wpfx_dataset').prop('disabled', true);
                    });
                }
    });
}

function adjustDataset2()
{

  jQuery('#wpfx_dataset2').html('<option>Loading datasets...</option>').prop('disabled', true);

    jQuery.ajax({dataType: "json", url: ajaxurl, type: "POST",
                data:
                {
                    'wpfx_form_key' : jQuery('#wpfx_form2 :selected').val(),
                                             'action'        : 'wpfx_get_dataset'
                }, success: function(data)
                {
                    jQuery('#wpfx_dataset2').find('option').remove();
                    jQuery('#wpfx_dataset2').prop('disabled', false);

                    jQuery.each(data, function(i,item)
                    {
                        jQuery('#wpfx_dataset2').append("<option value = '" + item.id + "'>" + item.date + "</option>");
                        if ( item.date == "This form does not contain datasets" )
                          jQuery('#wpfx_dataset2').prop('disabled', true);
                    });
                }
    });
}
// function adjustLayoutVisibility()
// {
//     var f = jQuery('#wpfx_layout :selected').val();
//     jQuery('#loader').show();
//
//     jQuery.ajax({dataType: "json", url: ajaxurl, type: "POST",
//                 data: { 'wpfx_layout' : f, 'action': 'wpfx_getlayoutvis' },
//                 success: function(data)
//                 {
//                     jQuery('#loader').hide();
//
//                     jQuery("#wpfx_layoutvis [value = '" + data.visible + "']").attr("selected", "selected");
//                     jQuery("#wpfx_clform [value = '" + data.form + "']").attr("selected", "selected");
//                 } });
// }

jQuery(document).ready(function()
{

  if ( !window.ajaxurl )
    return;

    jQuery('#wpfx_preview').click(function()
    {
        jQuery(this).attr('href', 'admin-ajax.php?action=frm_forms_preview&form=' + jQuery('#wpfx_form :selected').val());
    });

    jQuery('#wpfx_preview2').click(function()
    {
        jQuery(this).attr('href', 'admin-ajax.php?action=frm_forms_preview&form=' + jQuery('#wpfx_form2 :selected').val());
    });

    jQuery('#wpfx_layout').on('change', function()
    {
        onLayoutChange();
//         adjustLayoutVisibility();
    });


    jQuery('#clnewmap').click(function()
    {

        if ( jQuery('#clbody tr').length )
    {

        var row = jQuery('#clbody tr:last').clone(true);

    }
        else
    {


        var row = jQuery("<tr><td><input checked='checked' name = 'clname' type = 'radio' class = 'radioname' /></td><td><input  value='1' name = 'clfrom[]' /></td><td id = 'maps'>&raquo;&raquo;</td><td><input  value='1' name = 'clto[]' /></td><td id = 'delete'>&times;</td>").on('click', '#delete', function() {  jQuery(this).closest ('tr').remove (); recalcRadio(); } );
    }
        jQuery('#clbody').append(row);

        recalcRadio();
    });

    jQuery('#wpfx_form').on('change', function()
    {
        adjustLayout();
        adjustDataset();
        //adjustFormVisibility();
    });

    jQuery('#wpfx_form2').on('change', function()
    {
        adjustDataset2();
        //adjustFormVisibility();
    });
//     jQuery('#wpfx_layoutvis').on('change', function()
//     {
//         jQuery('#loader').show();
//
//         jQuery.ajax({dataType: "json", url: ajaxurl, type: "POST",
//                     data: {
//                         'wpfx_layout':            jQuery('#wpfx_layout :selected').val(),
//                         'wpfx_layout_visibility': jQuery('#wpfx_layoutvis :selected').val(),
//                         'action': 'wpfx_setlayoutvis'
//                     },
//                     success: function(data)
//                     {
//                         jQuery('#loader').hide();
//                     } });
//     });

    jQuery('#hideme').click(function()
    {
        jQuery('#dform').submit();

        return true;
    });

    jQuery('#remvcl').click(function()
    {
      var layout_name = jQuery('#wpfx_clname').val();
      var btn = jQuery(this);
      if ( !confirm('Are you sure you want to delete layout "'+layout_name+'"?') )
        return false;
      if ( !confirm('This action cannot be undone. Do you really want to delete layout "'+layout_name+'"?') )
        return false;
        if(true)
        {
            if(jQuery('#wpfx_layout :selected').val() == 3)
            {
                alert('Cant delete this');
            } else 
    {
      btn.prop('disabled', true);
      btn.val('Please wait...');
                jQuery.ajax({dataType: "json", url: ajaxurl, type: "POST",
                        data:
                        {
                            'wpfx_layout' : jQuery('#wpfx_layout :selected').val(),
                            'action'      : 'wpfx_del_layout'
                        }, success: function(data)
                        {
                            location.reload();
                        } });
        }
        }
    });

    jQuery('#savecl').click(function()
    {
    
    if(jQuery('#wpfx_layout :selected').val() == "3") 
    {



    }
        if( ! jQuery('#clbody tr').length)
        {
          var row = jQuery("<tr><td><input checked='checked' name = 'clname' type = 'radio' class = 'radioname' /></td><td><input value='1' name = 'clfrom[]' /></td><td id = 'maps'>&raquo;&raquo;</td><td><input  value='1' name = 'clto[]' /></td><td id = 'delete'>&times;</td>").on('click', '#delete', function() {  jQuery(this).closest ('tr').remove (); recalcRadio(); } );
          jQuery('#clbody').append(row);
        } else if(! jQuery("input:radio:checked").length )
        {
            alert('Please pick a name for dataset first');
            return false;
        }
        else
        {
            if(jQuery('#wpfx_layout :selected').val() > 3) // we're updating existing layout
            {
                jQuery('<input>').attr({
                    type: 'hidden',
                    id: 'update',
                    name: 'update'}).val('update').appendTo('#wpfx_layout_form');

                jQuery('<input>').attr({
                    type: 'hidden',
                    id: 'wpfx_layout_visibility',
                    name: 'wpfx_layout_visibility'}).val(jQuery('#wpfx_layoutvis :selected').val()).appendTo('#wpfx_layout_form');

                jQuery('<input>').attr({
                    type: 'hidden',
                    id: 'wpfx_clform',
                    name: 'wpfx_clform'}).val(jQuery('#wpfx_clform :selected').val()).appendTo('#wpfx_layout_form');


                jQuery('<input>').attr({
                    type: 'hidden',
                    id: 'wpfx_layout',
                    name: 'wpfx_layout'}).val(jQuery('#wpfx_layout :selected').val()).appendTo('#wpfx_layout_form');

            }

            return true;
        }
    });

    adjustLayout();
    onLayoutChange();
    adjustDataset();

    adjustDataset2();
//     adjustLayoutVisibility();
});
