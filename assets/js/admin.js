jQuery(function($){
  $(document).on('click','.bjm-media-pick',function(e){
    e.preventDefault();
    const frame = wp.media({title:'Select company logo',button:{text:'Use logo'},multiple:false});
    frame.on('select',function(){
      const attachment = frame.state().get('selection').first().toJSON();
      $('#bjm_company_logo_id').val(attachment.id);
    });
    frame.open();
  });
  $(document).on('change','.bjm-check-all',function(){
    const checked = $(this).is(':checked');
    $(this).closest('table').find('tbody input[type="checkbox"]').prop('checked',checked);
  });
});
