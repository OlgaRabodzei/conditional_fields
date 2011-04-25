(function ($) {

{
  $(document).ready(function() {
    if ($(':input[name="selector_custom"]').val() == '0') {
      $('#edit-selector')
      .after('<span id="cf-selector"><em>'+$('#edit-selector').val()+'</em> <a href="">'+Drupal.t('edit')+'</a></span>')
      .hide();
      $('#cf-selector a').click(function() {
        $('#cf-selector').hide();
        $('#edit-selector').show();
        $(':input[name="selector_custom"]').val('1');
        return false;
      });
    }
  });
}

})(jQuery);
