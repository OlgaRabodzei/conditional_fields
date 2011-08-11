(function ($) {

/**
 * Override states.js "state:visible" event for fields with custom effects.
 */
{

  Drupal.behaviors.conditionalFields = {
    attach: function (context, settings) {
      $.each(settings.conditionalFields.effects, function(dependent) {
        $('[name="'+dependent+'"]', context).unbind('state:visible').bind('state:visible', function(e) {
          if (e.trigger) {
            var effect = Drupal.settings.conditionalFields.effects[$(e.target).attr('name')];
            switch (effect.effect) {
              case 'fade':              
                $(e.target).closest('.form-item, .form-submit, .form-wrapper')[e.value ? 'fadeIn' : 'fadeOut'](parseInt(effect.options.speed));
                break;
              case 'slide':
                $(e.target).closest('.form-item, .form-submit, .form-wrapper')[e.value ? 'slideDown' : 'slideUp'](parseInt(effect.options.speed));
                break;
              default:
                // The "effect" variable is treated as a jQuery plugin.
                $(e.target)[effect.effect](e);
            }
          }
          // Prevent bubbling of the event to document.
          return false;
        });
      });
    }
  };

}

})(jQuery);
