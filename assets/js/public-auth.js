jQuery(document).ready(function ($) {
  // ========================================
  // CUSTOM MODAL SYSTEM
  // ========================================

  function showModal(config) {
    const {
      title = "",
      description = "",
      icon = "info",
      buttons = [{ text: "OK", type: "primary", action: null }],
      onClose = null,
    } = config;

    // Create overlay and modal HTML
    const overlayId =
      "scm-modal-overlay-" + Math.random().toString(36).substr(2, 9);
    const modalId = "scm-modal-" + Math.random().toString(36).substr(2, 9);

    const overlay = $("<div>")
      .addClass("scm-modal-overlay")
      .attr("id", overlayId);
    const modal = $("<div>").addClass("scm-modal").attr("id", modalId);

    // Build modal content
    let iconsMap = {
      success: "✓",
      error: "✕",
      warning: "!",
      info: "i",
    };

    const iconElem = $("<div>")
      .addClass("scm-modal-icon " + icon)
      .text(iconsMap[icon] || "•");

    const titleElem = $("<h2>").addClass("scm-modal-title").text(title);
    const descElem = $("<p>")
      .addClass("scm-modal-description")
      .text(description);

    const closeBtn = $("<button>")
      .addClass("scm-modal-close")
      .text("✕")
      .on("click", function () {
        closeModal(overlayId, modalId);
        if (onClose) onClose(false);
      });

    // Build buttons
    const buttonsContainer = $("<div>").addClass("scm-modal-buttons");
    buttons.forEach(function (btn) {
      const btnElem = $("<button>")
        .addClass("scm-modal-btn scm-modal-btn-" + (btn.type || "primary"))
        .text(btn.text)
        .on("click", function () {
          if (btn.action) btn.action();
          closeModal(overlayId, modalId);
          if (onClose && btn.closeModal !== false) onClose(true);
        });
      buttonsContainer.append(btnElem);
    });

    // Assemble modal
    const content = $("<div>").addClass("scm-modal-content");
    content.append(closeBtn, iconElem, titleElem, descElem, buttonsContainer);
    modal.append(content);

    // Add to page
    $("body").append(overlay, modal);

    // Trigger animation
    setTimeout(function () {
      overlay.addClass("active");
      modal.addClass("active");
    }, 10);

    // Close modal on overlay click
    overlay.on("click", function (e) {
      if (e.target === this) {
        closeModal(overlayId, modalId);
        if (onClose) onClose(false);
      }
    });

    // Close on ESC key
    $(document).on("keydown", function (e) {
      if (e.key === "Escape") {
        closeModal(overlayId, modalId);
        if (onClose) onClose(false);
      }
    });

    return { overlayId, modalId };
  }

  function closeModal(overlayId, modalId) {
    const overlay = $("#" + overlayId);
    const modal = $("#" + modalId);

    overlay.removeClass("active");
    modal.removeClass("active");

    setTimeout(function () {
      overlay.remove();
      modal.remove();
    }, 300);
  }

  // Tab switching functionality
  $(".scm-tab-button").on("click", function () {
    $(".scm-tab-button").removeClass("active");
    $(this).addClass("active");

    const tab = $(this).data("tab");
    $(".scm-tab-content").removeClass("active");
    $("#scm-" + tab + "-tab").addClass("active");
  });

  $(".scm-switch-tab").on("click", function (e) {
    e.preventDefault();
    const target = $(this).data("target");
    $(`.scm-tab-button[data-tab="${target}"]`).trigger("click");
  });

  // BD phone regex: 01XXXXXXXXX only
  const bdPhoneRegex = /^01[0-9]{9}$/;

  // Normalize phone number before sending (adds +88 automatically)
  function normalizePhone(phone) {
    phone = phone.trim();
    if (/^01[0-9]{9}$/.test(phone)) {
      phone = "+88" + phone;
    }
    return phone;
  }

  // Login form handling
  $("#scm-login-form").on("submit", function (e) {
    e.preventDefault();

    const form = $(this);
    const submitBtn = form.find('button[type="submit"]');
    let phone = normalizePhone(form.find('input[name="phone"]').val());

    if (!bdPhoneRegex.test(form.find('input[name="phone"]').val().trim())) {
      showModal({
        title: "Invalid Phone Number",
        description:
          "Please enter a valid Bangladesh phone number (01XXXXXXXXX).",
        icon: "warning",
        buttons: [{ text: "OK", type: "primary", action: null }],
      });
      return;
    }

    submitBtn.prop("disabled", true).text("Logging in...");

    $.ajax({
      url: scmAuth.ajaxurl,
      type: "POST",
      data: {
        action: "scm_login",
        nonce: $('#scm-login-form input[name="_wpnonce"]').val(),
        phone: phone,
      },
      success: function (response) {
        console.log("Login Response:", response);

        if (response.success) {
          showModal({
            title: "Login Successful",
            description:
              response.data.message || "You have been logged in successfully!",
            icon: "success",
            buttons: [{ text: "Continue", type: "primary", action: null }],
          });
          if (response.data.redirect) {
            setTimeout(() => {
              window.location.href = response.data.redirect;
            }, 500);
          }
        } else {
          showModal({
            title: "Login Failed",
            description:
              response.data.message || "Login failed. Please try again.",
            icon: "error",
            buttons: [{ text: "OK", type: "primary", action: null }],
          });
          submitBtn.prop("disabled", false).text("Login");
          if (response.data.redirect) {
            setTimeout(() => {
              window.location.href = response.data.redirect;
            }, 2000);
          }
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error("Login AJAX Error:", textStatus, errorThrown);
        showModal({
          title: "Login Error",
          description: "Something went wrong. Please try again.",
          icon: "error",
          buttons: [{ text: "OK", type: "primary", action: null }],
        });
        submitBtn.prop("disabled", false).text("Login");
      },
    });
  });

  // Signup form handling
  let signupTimer;
  let canResendOTP = true;

  $("#scm-signup-form").on("submit", function (e) {
    e.preventDefault();

    const form = $(this);
    const submitBtn = form.find('button[type="submit"]');
    let phone = normalizePhone(form.find('input[name="phone"]').val());
    const otpField = form.find('input[name="otp"]');

    if (!bdPhoneRegex.test(form.find('input[name="phone"]').val().trim())) {
      showModal({
        title: "Invalid Phone Number",
        description:
          "Please enter a valid Bangladesh phone number (01XXXXXXXXX).",
        icon: "warning",
        buttons: [{ text: "OK", type: "primary", action: null }],
      });
      return;
    }

    if (otpField.is(":visible")) {
      const otp = otpField.val().trim();
      if (!/^\d{6}$/.test(otp)) {
        showModal({
          title: "Invalid OTP",
          description: "Please enter a valid 6-digit OTP.",
          icon: "warning",
          buttons: [{ text: "OK", type: "primary", action: null }],
        });
        return;
      }
      verifyOTP(phone, otp, submitBtn);
      return;
    }

    sendOTP(phone, submitBtn, false);
  });

  $("#scm-resend-otp").on("click", function (e) {
    e.preventDefault();
    if (!canResendOTP) return;

    const form = $("#scm-signup-form");
    let phone = normalizePhone(form.find('input[name="phone"]').val());
    sendOTP(phone, $(this), true);
  });

  function sendOTP(phone, button, isResend) {
    button
      .prop("disabled", true)
      .text(isResend ? "Sending..." : "Sending OTP...");

    console.log("Sending OTP request with:", {
      url: scmAuth.ajaxurl,
      action: "scm_send_otp",
      nonce: scmAuth.signup_nonce,
      phone: phone,
      isResend: isResend,
    });

    $.ajax({
      url: scmAuth.ajaxurl,
      type: "POST",
      data: {
        action: "scm_send_otp",
        nonce: scmAuth.signup_nonce,
        phone: phone,
        resend: isResend,
      },
      success: function (response) {
        console.log("OTP Response:", response);

        if (response.success) {
          $("#signup-step-2").show();
          $("#signup-step-1 input[name='phone']").prop("readonly", true);

          button
            .text(isResend ? "Resend OTP" : "Verify OTP")
            .prop("disabled", false);

          if (response.data.dev_otp) {
            console.log("Development OTP:", response.data.dev_otp);
          }
          if (response.data.debug) {
            console.log("Debug Info:", response.data.debug);
          }

          startResendTimer();
        } else {
          showModal({
            title: "OTP Error",
            description:
              response.data.message || "Failed to send OTP. Please try again.",
            icon: "error",
            buttons: [{ text: "OK", type: "primary", action: null }],
          });
          if (response.data.debug)
            console.log("Error Debug Info:", response.data.debug);
          button
            .prop("disabled", false)
            .text(isResend ? "Resend OTP" : "Send OTP");
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error("AJAX Error:", {
          status: jqXHR.status,
          statusText: jqXHR.statusText,
          responseText: jqXHR.responseText,
          error: errorThrown,
        });
        showModal({
          title: "Network Error",
          description:
            "Something went wrong. Please check your connection and try again.",
          icon: "error",
          buttons: [{ text: "OK", type: "primary", action: null }],
        });
        button
          .prop("disabled", false)
          .text(isResend ? "Resend OTP" : "Send OTP");
      },
    });
  }

  function verifyOTP(phone, otp, button) {
    button.prop("disabled", true).text("Verifying...");

    console.log("Sending verify OTP request with:", {
      url: scmAuth.ajaxurl,
      action: "scm_verify_otp",
      nonce: scmAuth.signup_nonce,
      phone: phone,
      otp: otp,
    });

    $.ajax({
      url: scmAuth.ajaxurl,
      type: "POST",
      data: {
        action: "scm_verify_otp",
        nonce: scmAuth.signup_nonce,
        phone: phone,
        otp: otp,
      },
      success: function (response) {
        if (response.success) {
          window.location.href = response.data.redirect;
        } else {
          showModal({
            title: "Verification Failed",
            description:
              response.data.message || "Invalid OTP. Please try again.",
            icon: "error",
            buttons: [{ text: "OK", type: "primary", action: null }],
          });
          if (response.data && response.data.debug)
            console.log("Verify debug:", response.data.debug);
          button.prop("disabled", false).text("Verify OTP");
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error("AJAX Error (verify):", {
          status: jqXHR.status,
          statusText: jqXHR.statusText,
          responseText: jqXHR.responseText,
          error: errorThrown,
          textStatus: textStatus,
        });
        showModal({
          title: "Verification Error",
          description:
            "Something went wrong while verifying. Please try again.",
          icon: "error",
          buttons: [{ text: "OK", type: "primary", action: null }],
        });
        button.prop("disabled", false).text("Verify OTP");
      },
    });
  }

  function startResendTimer() {
    let timeLeft = 60;
    canResendOTP = false;
    const resendBtn = $("#scm-resend-otp");

    clearInterval(signupTimer);
    resendBtn.prop("disabled", true);

    signupTimer = setInterval(function () {
      if (timeLeft <= 0) {
        clearInterval(signupTimer);
        resendBtn.prop("disabled", false).text("Resend OTP");
        canResendOTP = true;
        return;
      }
      resendBtn.text(`Resend in ${timeLeft}s`);
      timeLeft--;
    }, 1000);
  }

  // Registration form handling
  $("#scm-registration-form").on("submit", function (e) {
    e.preventDefault();

    const form = $(this);
    const submitBtn = form.find('button[type="submit"]');
    const formArray = form.serializeArray();
    const data = {
      action: "scm_complete_registration",
      nonce: scmAuth.signup_nonce,
      district: "Dhaka",
    };
    formArray.forEach(function (field) {
      data[field.name] = field.value;
    });

    if (!data.phone) {
      const phoneFromDom = form.find('input[name="phone"]').val();
      if (phoneFromDom) data.phone = normalizePhone(phoneFromDom);
    } else {
      data.phone = normalizePhone(data.phone);
    }

    if (data.name) data.name = data.name.trim();
    if (!data.name) {
      showModal({
        title: "Name Required",
        description: "Please enter your full name.",
        icon: "warning",
        buttons: [{ text: "OK", type: "primary", action: null }],
      });
      return;
    }
    if (!data.phone) {
      showModal({
        title: "Phone Verification Required",
        description:
          "Phone number missing or verification expired. Please verify your phone again.",
        icon: "warning",
        buttons: [{ text: "OK", type: "primary", action: null }],
      });
      return;
    }

    submitBtn.prop("disabled", true).text("Registering...");

    $.ajax({
      url: scmAuth.ajaxurl,
      type: "POST",
      data: data,
      success: function (response) {
        if (response.success) {
          window.location.href = response.data.redirect;
        } else {
          showModal({
            title: "Registration Failed",
            description:
              response.data.message || "Registration failed. Please try again.",
            icon: "error",
            buttons: [{ text: "OK", type: "primary", action: null }],
          });
          submitBtn.prop("disabled", false).text("Complete Registration");
        }
      },
      error: function (jqXHR) {
        console.error(
          "Registration AJAX error:",
          jqXHR.responseText || jqXHR.statusText
        );
        showModal({
          title: "Registration Error",
          description: "Something went wrong. Please try again.",
          icon: "error",
          buttons: [{ text: "OK", type: "primary", action: null }],
        });
        submitBtn.prop("disabled", false).text("Complete Registration");
      },
    });
  });
  /****************************************************
   * INLINE PROFILE EDITING (Dashboard)
   ****************************************************/

  // When user clicks EDIT
  $("#scm-edit-btn").on("click", function () {
    $(".scm-value").hide();
    $(".scm-edit-field").show();
    $("#scm-edit-btn").hide();
    $("#scm-save-btn").show();
  });

  // When user clicks SAVE
  $("#scm-save-btn").on("click", function () {
    let data = {
      action: "scm_update_profile_inline",
      nonce: scmAuth.update_profile_nonce, // Make sure this is localized
    };

    // Map HTML data-key → DB keys
    const keyMapping = {
      first_name: "first_name", // WP user field
      last_name: "last_name", // WP user field
      phone: "scm_phone", // User meta with prefix
      address: "scm_address", // User meta with prefix
      district: "scm_district",
    };

    // Collect only allowed fields
    $(".scm-edit-field").each(function () {
      let key = $(this).data("key");
      if (keyMapping[key]) {
        data[keyMapping[key]] = $(this).val();
      }
    });

    $.ajax({
      url: scmAuth.ajaxurl,
      type: "POST",
      data: data,
      beforeSend: () => {
        $("#scm-save-btn").text("Saving...").prop("disabled", true);
      },
      success: function (response) {
        if (response.success) {
          // Update visible values instantly
          $(".scm-edit-field").each(function () {
            let key = $(this).data("key");
            if (keyMapping[key]) {
              let val = $(this).val();
              $('.scm-value[data-key="' + key + '"]').text(val);
            }
          });

          // Switch UI back to read mode
          $(".scm-edit-field").hide();
          $(".scm-value").show();
          $("#scm-save-btn").hide().text("Save").prop("disabled", false);
          $("#scm-edit-btn").show();

          showModal({
            title: "Profile Updated",
            description: "Your profile has been updated successfully!",
            icon: "success",
            buttons: [{ text: "OK", type: "primary", action: null }],
          });
        } else {
          showModal({
            title: "Update Failed",
            description:
              response.data.message || "Failed to update your profile.",
            icon: "error",
            buttons: [{ text: "OK", type: "primary", action: null }],
          });
          $("#scm-save-btn").prop("disabled", false).text("Save");
        }
      },
      error: function (jqXHR) {
        console.error("Inline Update Error:", jqXHR.responseText);
        showModal({
          title: "Save Error",
          description: "Something went wrong while saving your profile.",
          icon: "error",
          buttons: [{ text: "OK", type: "primary", action: null }],
        });
        $("#scm-save-btn").prop("disabled", false).text("Save");
      },
    });
  });

  /****************************************************
   * APARTMENT MANAGEMENT (Manager Dashboard)
   ****************************************************/

  // Load apartments on page load
  function loadApartments() {
    if ($("#scm-apartments-tbody").length === 0) return; // Not on manager page

    $.ajax({
      url: scmAuth.ajaxurl,
      type: "POST",
      data: {
        action: "scm_get_apartments",
        nonce: scmAuth.user_nonce,
      },
      success: function (response) {
        if (response.success && response.data.apartments) {
          const apartments = response.data.apartments;
          const container = $("#scm-apartments-tbody");
          container.empty();

          if (apartments.length === 0) {
            container.append(
              '<div class="scm-empty-state">' +
                "<p>No apartments yet. Add one using the form above.</p>" +
                "</div>"
            );
            return;
          }

          apartments.forEach(function (apt) {
            const createdDate = new Date(apt.created_at).toLocaleDateString();
            const updatedDate = apt.updated_at
              ? new Date(apt.updated_at).toLocaleDateString()
              : "-";

            container.append(
              '<div class="scm-apartment-card" data-apartment-id="' +
                apt.id +
                '">' +
                '<div class="scm-apartment-card-header">' +
                "<h3>" +
                escapeHtml(apt.name) +
                "</h3>" +
                "</div>" +
                '<div class="scm-apartment-card-body">' +
                '<div class="scm-apartment-info-row">' +
                '<span class="scm-apartment-label">Location:</span>' +
                '<span class="scm-apartment-value">' +
                escapeHtml(apt.location) +
                "</span>" +
                "</div>" +
                '<div class="scm-apartment-info-row">' +
                '<span class="scm-apartment-label">Created:</span>' +
                '<span class="scm-apartment-value">' +
                createdDate +
                "</span>" +
                "</div>" +
                '<div class="scm-apartment-info-row">' +
                '<span class="scm-apartment-label">Updated:</span>' +
                '<span class="scm-apartment-value">' +
                updatedDate +
                "</span>" +
                "</div>" +
                "</div>" +
                '<div class="scm-apartment-card-footer">' +
                '<button class="scm-edit-apartment-btn scm-btn-secondary" data-apartment-id="' +
                apt.id +
                '" data-name="' +
                escapeAttr(apt.name) +
                '" data-location="' +
                escapeAttr(apt.location) +
                '">✎ Edit</button>' +
                '<button class="scm-delete-apartment-btn scm-btn-danger" data-apartment-id="' +
                apt.id +
                '">🗑 Delete</button>' +
                "</div>" +
                "</div>"
            );
          });
        } else {
          $("#scm-apartments-tbody").html(
            '<div class="scm-error-state"><p>Failed to load apartments.</p></div>'
          );
        }
      },
      error: function () {
        $("#scm-apartments-tbody").html(
          '<div class="scm-error-state"><p>Error loading apartments.</p></div>'
        );
      },
    });
  }

  // Helper function to escape HTML
  function escapeHtml(text) {
    if (!text) return "";
    const map = {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#039;",
    };
    return text.replace(/[&<>"']/g, function (m) {
      return map[m];
    });
  }

  // Helper function to escape attribute values
  function escapeAttr(text) {
    if (!text) return "";
    return text
      .replace(/&/g, "&amp;")
      .replace(/"/g, "&quot;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;");
  }

  // Add apartment form submission
  $("#scm-apartment-form").on("submit", function (e) {
    e.preventDefault();

    const form = $(this);
    const submitBtn = form.find('button[type="submit"]');
    const responseDiv = $("#scm-apartment-form-response");

    const apartmentName = form.find("#apartment-name").val().trim();
    const apartmentLocation = form.find("#apartment-location").val().trim();

    if (!apartmentName || !apartmentLocation) {
      showModal({
        title: "Missing Information",
        description: "Please fill in all apartment fields.",
        icon: "warning",
        buttons: [{ text: "OK", type: "primary", action: null }],
      });
      return;
    }

    submitBtn.prop("disabled", true).text("Adding...");

    $.ajax({
      url: scmAuth.ajaxurl,
      type: "POST",
      data: {
        action: "scm_add_apartment",
        nonce: scmAuth.user_nonce,
        name: apartmentName,
        location: apartmentLocation,
      },
      success: function (response) {
        if (response.success) {
          showModal({
            title: "Apartment Added",
            description: "The apartment has been added successfully!",
            icon: "success",
            buttons: [{ text: "OK", type: "primary", action: null }],
          });
          form[0].reset();
          loadApartments(); // Refresh list
          submitBtn.prop("disabled", false).text("Add Apartment");
        } else {
          showModal({
            title: "Add Failed",
            description: response.data.message || "Failed to add apartment.",
            icon: "error",
            buttons: [{ text: "OK", type: "primary", action: null }],
          });
          submitBtn.prop("disabled", false).text("Add Apartment");
        }
      },
      error: function () {
        showModal({
          title: "Add Error",
          description: "An error occurred while adding the apartment.",
          icon: "error",
          buttons: [{ text: "OK", type: "primary", action: null }],
        });
        submitBtn.prop("disabled", false).text("Add Apartment");
      },
    });
  });

  // Edit apartment button
  $(document).on("click", ".scm-edit-apartment-btn", function () {
    const apartmentId = $(this).data("apartment-id");
    const apartmentName = $(this).data("name");
    const apartmentLocation = $(this).data("location");

    // Show edit form (could be inline or modal)
    const editForm = $(
      '<div class="scm-edit-apartment-form">' +
        '<div class="scm-form-group">' +
        "<label>Apartment Name</label>" +
        '<input type="text" class="scm-edit-apt-name" value="' +
        escapeAttr(apartmentName) +
        '">' +
        "</div>" +
        '<div class="scm-form-group">' +
        "<label>Location</label>" +
        '<input type="text" class="scm-edit-apt-location" value="' +
        escapeAttr(apartmentLocation) +
        '">' +
        "</div>" +
        '<button class="scm-save-edit-apt-btn" data-apartment-id="' +
        apartmentId +
        '">Save Changes</button>' +
        '<button class="scm-cancel-edit-apt-btn">Cancel</button>' +
        "</div>"
    );

    const row = $(
      ".scm-apartment-row[data-apartment-id='" + apartmentId + "']"
    );
    row.after(editForm);
  });

  // Save edited apartment
  $(document).on("click", ".scm-save-edit-apt-btn", function () {
    const btn = $(this);
    const apartmentId = btn.data("apartment-id");
    const editForm = btn.closest(".scm-edit-apartment-form");
    const apartmentName = editForm.find(".scm-edit-apt-name").val().trim();
    const apartmentLocation = editForm
      .find(".scm-edit-apt-location")
      .val()
      .trim();

    if (!apartmentName || !apartmentLocation) {
      showModal({
        title: "Missing Information",
        description: "Please fill in all apartment fields.",
        icon: "warning",
        buttons: [{ text: "OK", type: "primary", action: null }],
      });
      return;
    }

    btn.prop("disabled", true).text("Saving...");

    $.ajax({
      url: scmAuth.ajaxurl,
      type: "POST",
      data: {
        action: "scm_update_apartment",
        nonce: scmAuth.user_nonce,
        apartment_id: apartmentId,
        name: apartmentName,
        location: apartmentLocation,
      },
      success: function (response) {
        if (response.success) {
          showModal({
            title: "Apartment Updated",
            description: "The apartment has been updated successfully!",
            icon: "success",
            buttons: [{ text: "OK", type: "primary", action: null }],
          });
          loadApartments(); // Refresh list
          $(".scm-edit-apartment-form").remove();
        } else {
          showModal({
            title: "Update Failed",
            description: response.data.message || "Failed to update apartment.",
            icon: "error",
            buttons: [{ text: "OK", type: "primary", action: null }],
          });
          btn.prop("disabled", false).text("Save Changes");
        }
      },
      error: function () {
        showModal({
          title: "Update Error",
          description: "An error occurred while updating the apartment.",
          icon: "error",
          buttons: [{ text: "OK", type: "primary", action: null }],
        });
        btn.prop("disabled", false).text("Save Changes");
      },
    });
  });

  // Cancel edit
  $(document).on("click", ".scm-cancel-edit-apt-btn", function () {
    $(this).closest(".scm-edit-apartment-form").remove();
  });

  // Delete apartment button
  $(document).on("click", ".scm-delete-apartment-btn", function () {
    const apartmentId = $(this).data("apartment-id");
    const btn = $(this);

    showModal({
      title: "Delete Apartment?",
      description:
        "Are you sure you want to delete this apartment? This action cannot be undone.",
      icon: "warning",
      buttons: [
        { text: "Cancel", type: "secondary", action: null },
        {
          text: "Delete",
          type: "danger",
          action: function () {
            btn.prop("disabled", true).text("Deleting...");

            $.ajax({
              url: scmAuth.ajaxurl,
              type: "POST",
              data: {
                action: "scm_delete_apartment",
                nonce: scmAuth.user_nonce,
                apartment_id: apartmentId,
              },
              success: function (response) {
                if (response.success) {
                  showModal({
                    title: "Deleted",
                    description: "The apartment has been deleted successfully!",
                    icon: "success",
                    buttons: [{ text: "OK", type: "primary", action: null }],
                  });
                  loadApartments(); // Refresh list
                } else {
                  showModal({
                    title: "Delete Failed",
                    description:
                      response.data.message || "Failed to delete apartment.",
                    icon: "error",
                    buttons: [{ text: "OK", type: "primary", action: null }],
                  });
                  btn.prop("disabled", false).text("Delete");
                }
              },
              error: function () {
                showModal({
                  title: "Delete Error",
                  description:
                    "An error occurred while deleting the apartment.",
                  icon: "error",
                  buttons: [{ text: "OK", type: "primary", action: null }],
                });
                btn.prop("disabled", false).text("Delete");
              },
            });
          },
        },
      ],
    });
  });

  // Load apartments on dashboard page load
  if ($("#scm-apartments-tbody").length > 0) {
    loadApartments();
  }

  /****************************************************
   * LOGOUT FUNCTIONALITY
   ****************************************************/

  // Logout button handler
  $(document).on("click", "#scm-logout-btn", function (e) {
    e.preventDefault();

    showModal({
      title: "Logout?",
      description: "Are you sure you want to logout?",
      icon: "warning",
      buttons: [
        { text: "Cancel", type: "secondary", action: null },
        {
          text: "Logout",
          type: "danger",
          action: function () {
            $.ajax({
              url: scmAuth.ajaxurl,
              type: "POST",
              data: {
                action: "scm_logout",
                nonce: scmAuth.user_nonce,
              },
              success: function (response) {
                if (response.success) {
                  showModal({
                    title: "Logged Out",
                    description: "You have been logged out successfully!",
                    icon: "success",
                    buttons: [{ text: "OK", type: "primary", action: null }],
                  });
                  setTimeout(function () {
                    if (response.data.redirect) {
                      window.location.href = response.data.redirect;
                    } else {
                      window.location.reload();
                    }
                  }, 1000);
                } else {
                  showModal({
                    title: "Logout Failed",
                    description: response.data.message || "Logout failed.",
                    icon: "error",
                    buttons: [{ text: "OK", type: "primary", action: null }],
                  });
                }
              },
              error: function () {
                showModal({
                  title: "Logout Error",
                  description: "An error occurred while logging out.",
                  icon: "error",
                  buttons: [{ text: "OK", type: "primary", action: null }],
                });
              },
            });
          },
        },
      ],
    });
  });
});
