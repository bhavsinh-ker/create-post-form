/**
 * save my post
 *
 * Description. this function will allow user to send ajax request for save post
 *
 * @param {objectVar}   formElement   Form Element.
 */
function saveMyPost(formElement) {
    jQuery(document).ready(function ($) {
        var alartElement = $(formElement).find(".alart");
        $('textarea[name="description"]').val(tinymce.get('description').getContent());
        var errorMessage = 'Something is wrong! please try again.';
        $.ajax({
            type: "post",
            url: createPostFormConfig.ajaxUrl,
            data: new FormData(formElement),
            processData: false,
            contentType: false,
            beforeSend: function () {
                formLoading(true, formElement);
            },
            success: function (response) {
                try {
                    if (response == "" || !response) {
                        alartMessage(errorMessage, 'error', alartElement);
                        return;
                    }
                    var result = JSON.parse(response);
                    if (result.status === false && result.message != "") {
                        if (result.message == "validation error") {
                            for (var i = 0; i < result.data.length; i++) {
                                alartMessage(result.data[i].message, 'warning', alartElement);
                            }
                            return;
                        }
                        alartMessage(result.message, 'error', alartElement);
                    } else if (result.status === true && result.message != "") {
                        alartMessage(result.message, 'success', alartElement);
                        $(formElement).trigger("reset");
                    } else {
                        alartMessage(errorMessage, 'error', alartElement);
                    }
                }
                catch (ex) {
                    alartMessage(errorMessage, 'error', alartElement);
                }
            },
            error: function (xhr) {
                alartMessage(xhr.responseText, 'error', alartElement);
            },
            complete: function () {
                formLoading(false, formElement);
            }
        });
    });
}

/**
 * Form Loading
 *
 * This function will halp for handle form loading process
 *
 * @param {status}      boolean     true for enable the loading process and false for disable the oading process.
 * @param {object}      element     Form Element.
 */
function formLoading(status, formElement) {
    jQuery(document).ready(function ($) {
        if (status == true) {
            $(formElement).find(".loader").fadeIn();
            $(formElement).find(".alart").html('');
            $(formElement).find('button[type="submit"]').attr("disabled", "disabled");

        } else {
            $(formElement).find(".loader").fadeOut();
            $(formElement).find('button[type="submit"]').removeAttr("disabled");
            scrollToElement($(formElement).parent());
        }
    });
}

/**
 * Scroll to element
 *
 * This function will help for smooth scroll to element
 *
 * @param {object}      element     Form Element.
 */
function scrollToElement(element) {
    jQuery(document).ready(function ($) {
        $('html, body').animate({
            scrollTop: $(element).offset().top
        }, 800);
    });
}

/**
 * Scroll to element
 *
 * This function will help for smooth scroll to element
 *
 * @param {string}     message      message to print.
 * @param {string}     type         success, warning and error.
 * @param {Object}     element      Alart Element.
 */
function alartMessage(message, type, element) {
    jQuery(document).ready(function ($) {
        if (message == "" && type == "") {
            $(element).html('');
            return;
        }
        $(element).append('<div class="alart alart-' + type + '">' + message + '</div>');
        return;
    });
}