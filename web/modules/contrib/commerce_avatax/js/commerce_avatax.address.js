(function($, Drupal, drupalSettings) {

  var isValidating = false;

  // A named function for preventing form submission, so that it can be removed.
  function preventFormSubmit(event) {
    event.preventDefault();
  }
  function submitForm($form) {
    isValidating = false;
    $form[0].removeEventListener("submit", preventFormSubmit, true);
    $form.find(":input.button--primary").click();
  }

  function getConfirmationDialog(content, title, buttons) {
    return Drupal.dialog($('<div class="address-suggestions">' + content + '</div>'), {
      title: title,
      dialogClass: 'address-format-modal',
      resizable: false,
      buttons: buttons,
      closeOnEscape: false,
      maxWidth: "80%",
      draggable: false,
      close: function close(event) {
        isValidating = false;
        Drupal.dialog(event.target).close();
        Drupal.detachBehaviors(event.target, null, 'unload');
        $(event.target).remove();
      }
    })
  }

  Drupal.behaviors.commerceAvatax = {
    attach: function attach(context) {

      if (!drupalSettings.commerceAvatax || !drupalSettings.commerceAvatax.address || !drupalSettings.commerceAvatax.country) {
        return;
      }

      var $form = $('.avatax-form', context).closest('form');
      $form.once('.avatax-form-processed').each(function () {
        var formEl = $form[0];

        // Add event listener to the form. In case when we performing
        // address validation other modules could try to submit form.
        // Prevent that until we don't finish with address validation.
        formEl.addEventListener("submit", preventFormSubmit, true);

        $form.on('submit.commerce_avatax', function () {
          var allowFormSubmit = true;
          // Get data from module.
          var address = drupalSettings.commerceAvatax.address;
          var $inlineForm = $('#' + drupalSettings.commerceAvatax.inline_id);
          var $addressSuggestionEl = $inlineForm.find('[name*="address_suggestion"]')

          // We have some value, continue with from submit.
          if ($addressSuggestionEl.val().length > 0) {
            formEl.removeEventListener("submit", preventFormSubmit, true);
            return allowFormSubmit;
          }

          // Check if this submit handler is already performing validation and triggered again by another module.
          if (isValidating) {
            return false;
          }
          isValidating = true;

          // If we don't have country code or we adding or editing address,
          // pickup new values.
          if (address.country_code === null || !drupalSettings.commerceAvatax.rendered) {
            drupalSettings.commerceAvatax.fields.forEach(function (i) {
              address[i] = $inlineForm.find('[name*="' + i + ']"]').val()
            });
          }

          // If country code matches those which we need to validate.
          if (drupalSettings.commerceAvatax.countries.hasOwnProperty(address.country_code) && $('.address-format-modal').length === 0) {
            // Do not submit form automatically, we need to attempt address validation.
            allowFormSubmit = false;

            $.ajax({
              async: true,
              url: drupalSettings.commerceAvatax.endpoint ,
              type: 'POST',
              data: JSON.stringify(address),
              dataType: 'json',
              success: function success(response) {
                if (response.output) {
                  var actions = [{
                    text: Drupal.t('Let me change the address'),
                    class: 'button button--primary',
                    id: 'button-again',
                    click: function click() {
                      isValidating = false;
                      confirmationDialog.close();
                    }
                  }, {
                    text: Drupal.t('Use the address anyway'),
                    class: 'button',
                    id: 'button-entered',
                    click: function click() {
                      $addressSuggestionEl.val('original');
                      confirmationDialog.close();
                      submitForm($form);
                    }
                  }];

                  // If we have proper suggestion.
                  if (response.payload) {
                    actions = [{
                      text: Drupal.t('Use recommended'),
                      class: 'button button--primary',
                      id: 'button-recommended',
                      click: function click() {
                        $addressSuggestionEl.val(response.payload);
                        // Even we sent payload, still pre-fill fields,
                        // if something else on the other panes fail,
                        // address should be there.
                        // We could also split by rendered flag how we fill
                        // data with rendered variable, but we still can't
                        // But we still can't mark address as validated
                        // without using submit handler in inline form.
                        drupalSettings.commerceAvatax.fields.map(function (i) {
                          $inlineForm.find('[name*="' + i + ']"]').val(response.suggestion[i]);
                        });
                        confirmationDialog.close();
                        submitForm($form);
                      }
                    }, {
                      text: Drupal.t('Use as entered'),
                      class: 'button',
                      id: 'button-entered',
                      click: function click() {
                        $addressSuggestionEl.val('original');
                        confirmationDialog.close();
                        submitForm($form);
                      }
                    },
                      {
                        text: Drupal.t('Enter again'),
                        class: 'button',
                        id: 'button-again',
                        click: function click(event) {
                          isValidating = false;
                          confirmationDialog.close();
                        }
                      }];
                  }

                  var confirmationDialog = getConfirmationDialog(response.output, Drupal.t('Confirm your shipping address'), actions);
                  confirmationDialog.showModal();
                }
                else {
                  // The address had no suggestions.
                  $addressSuggestionEl.val('original');
                  allowFormSubmit = true;
                  submitForm($form);
                }
              },
              error: function error() {
                var actions = [{
                  text: Drupal.t('Let me change the address'),
                  class: 'button button--primary',
                  id: 'button-again',
                  click: function click() {
                    confirmationDialog.close()
                  }
                }, {
                  text: Drupal.t('Use the address anyway'),
                  class: 'button',
                  id: 'button-entered',
                  click: function click(event) {
                    $addressSuggestionEl.val('original');
                    confirmationDialog.close()
                    submitForm($form);
                  }
                }];
                var confirmationDialog = getConfirmationDialog(
                  Drupal.t('We could not validate the address entered. Please check that you have entered the correct address'),
                  Drupal.t('Confirm your shipping address'),
                  actions
                );
                confirmationDialog.showModal();
              },
            });
          }

          // When we don't need to validate anything, submit form.
          if (allowFormSubmit) {
            formEl.removeEventListener("submit", preventFormSubmit, true);
          }

          return allowFormSubmit;
        });
      });
    },

    detach: function (context, settings, trigger) {
      if (trigger !== 'unload') {
        return;
      }

      var $form = $('.avatax-form', context).closest('form');
      if ($form.length === 0) {
        return;
      }

      $form.off('submit.commerce_avatax');
      $form[0].removeEventListener("submit", preventFormSubmit, true);
    },
  };
})(jQuery, Drupal, drupalSettings);
