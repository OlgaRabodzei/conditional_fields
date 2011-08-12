(function ($) {

/**
 * Override states.js "state:visible" event for fields with custom effects.
 */
{

  Drupal.behaviors.conditionalFields = {
    attach: function (context, settings) {
      $.each(settings.conditionalFields.effects, function(dependent) {
        var effect = settings.conditionalFields.effects[dependent];
        switch (effect.effect) {
          case 'fade':
            $('[name="'+dependent+'"]', context).unbind('state:visible').bind('state:visible', function(e) {
              if (e.trigger) {
                $(e.target).closest('.form-item, .form-submit, .form-wrapper')[e.value ? 'fadeIn' : 'fadeOut'](parseInt(effect.options.speed));
              }
              // Prevent bubbling of the event to document.
              return false;
            });
            break;

          case 'slide':
            $('[name="'+dependent+'"]', context).unbind('state:visible').bind('state:visible', function(e) {
              if (e.trigger) {
                $(e.target).closest('.form-item, .form-submit, .form-wrapper')[e.value ? 'slideDown' : 'slideUp'](parseInt(effect.options.speed));
              }
              return false;
            });
            break;

          case 'fill':
          case 'empty':
            $('[name="'+dependent+'"]', context).bind('state:empty', function(e) {
              if (e.trigger) {
                if (effect.options.reset) {
                  if (typeof oldValue == 'undefined' || $(e.target).val() != effect.options.value) {
                    oldValue = $(e.target).val();
                  }
                  $(e.target).val((effect.effect == 'fill' ? e.value : !e.value) ? oldValue : effect.options.value);
                }
                else {
                  if (effect.effect == 'fill' && !e.value || effect.effect == 'empty' && e.value) {
                    $(e.target).val(effect.options.value);
                  }
                }
              }
            });
            break;

          default:
            // The "effect" variable is treated as a jQuery plugin.
            $(e.target)[effect.effect](e, effect.options);
        }
      });
    }
  };

}

})(jQuery);
