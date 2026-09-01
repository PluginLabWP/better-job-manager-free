jQuery(function($){
  function gatherFilters($wrap, page){
    const defaultsRaw = $wrap.attr('data-defaults') || '{}';
    let defaults = {};
    try { defaults = JSON.parse(defaultsRaw); } catch(e) { defaults = {}; }
    const $form = $wrap.find('.bjm-jobs-form');
    const data = {
      action: 'bjm_filter_jobs',
      nonce: bjmData.nonce,
      keyword: $form.find('[name="keyword"]').val() || defaults.keyword || '',
      featured: $form.find('[name="featured"]').val() || defaults.featured || '',
      work_mode: $form.find('[name="work_mode"]').val() || defaults.work_mode || '',
      category: $form.find('[name="category"]').val() || defaults.category || '',
      type: $form.find('[name="type"]').val() || defaults.type || '',
      region: $form.find('[name="region"]').val() || defaults.region || '',
      orderby: $form.find('[name="orderby"]').val() || defaults.orderby || 'date',
      order: defaults.order || 'DESC',
      show_excerpt: defaults.show_excerpt || '1',
      per_page: $wrap.data('per-page') || 12,
      paged: page || 1
    };
    return data;
  }

  function runFilter($wrap, page){
    const data = gatherFilters($wrap, page);
    $wrap.find('.bjm-jobs-results').addClass('is-loading');
    $.post(bjmData.ajaxUrl, data, function(resp){
      $wrap.find('.bjm-jobs-results').removeClass('is-loading');
      if(resp && resp.success){
        $wrap.find('.bjm-jobs-results').html(resp.data.html);
      }
    });
  }

  $(document).on('submit','.bjm-jobs-form',function(e){
    e.preventDefault();
    runFilter($(this).closest('.bjm-jobs-wrap'), 1);
  });

  $(document).on('click','.bjm-page-link',function(e){
    e.preventDefault();
    runFilter($(this).closest('.bjm-jobs-wrap'), parseInt($(this).data('page'),10) || 1);
  });

  $(document).on('click','.bjm-reset-filters',function(){
    const $wrap = $(this).closest('.bjm-jobs-wrap');
    const $form = $wrap.find('.bjm-jobs-form');
    $form[0].reset();
    runFilter($wrap, 1);
  });

  $(document).on('click','.bjm-toggle-details',function(){
    const target = $(this).data('target');
    const $target = $('#' + target);
    const isHidden = $target.prop('hidden');
    $target.prop('hidden', !isHidden);
    $(this).text(isHidden ? 'Hide details' : 'View details');
  });

  $(document).on('click','.bjm-reveal-apply-form',function(e){
    e.preventDefault();
    const target = $(this).data('target');
    const $target = $('#' + target);
    if (!$target.length) {
      return;
    }
    const isHidden = $target.prop('hidden');
    $target.prop('hidden', !isHidden);
    $(this).attr('aria-expanded', isHidden ? 'true' : 'false');
    $(this).text(isHidden ? 'Hide application form' : 'Apply for this job');
  });
});
