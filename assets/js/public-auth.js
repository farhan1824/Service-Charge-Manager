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

  /****************************************************
   * APARTMENT & FLAT SELECTION (Registration for Flatholder)
   ****************************************************/

  // Load apartments when flatholder role is selected
  $(document).on("change", 'input[name="scm_role"]', function () {
    const selectedRole = $('input[name="scm_role"]:checked').val();

    if (selectedRole === "flatholder") {
      $(".scm-apartment-selection").slideDown();
      $(".scm-flat-selection").slideUp();
      $(".scm-flat-dropdown").val(""); // Reset flat selection

      // Load apartments
      loadApartmentsForRegistration();
    } else {
      $(".scm-apartment-selection").slideUp();
      $(".scm-flat-selection").slideUp();
      $(".scm-apartment-dropdown").val("");
      $(".scm-flat-dropdown").val("");
    }
  });

  // Load apartments from database
  function loadApartmentsForRegistration() {
    $.ajax({
      url: scmAuth.ajaxurl,
      type: "POST",
      data: {
        action: "scm_get_apartments_public",
        nonce: scmAuth.signup_nonce,
      },
      success: function (response) {
        if (response.success && response.data.apartments) {
          const apartments = response.data.apartments;
          const dropdown = $(".scm-apartment-dropdown");
          dropdown.find("option:not(:first)").remove(); // Remove all except first option

          apartments.forEach(function (apt) {
            dropdown.append(
              $("<option>").val(apt.id).text(escapeHtml(apt.name))
            );
          });
        } else {
          $(".scm-apartment-selection .scm-help-text")
            .text(__("No apartments available", "service-charge-manager"))
            .show();
        }
      },
      error: function () {
        $(".scm-apartment-selection .scm-help-text")
          .text(__("Failed to load apartments", "service-charge-manager"))
          .show();
      },
    });
  }

  // Load flats when apartment is selected
  $(document).on("change", ".scm-apartment-dropdown", function () {
    const apartmentId = $(this).val();

    if (apartmentId) {
      $(".scm-flat-selection").slideDown();
      loadFlatsForRegistration(apartmentId);
    } else {
      $(".scm-flat-selection").slideUp();
      $(".scm-flat-dropdown").find("option:not(:first)").remove();
    }
  });

  // Load flats from database
  function loadFlatsForRegistration(apartmentId) {
    $.ajax({
      url: scmAuth.ajaxurl,
      type: "POST",
      data: {
        action: "scm_get_flats_public",
        nonce: scmAuth.signup_nonce,
        apartment_id: apartmentId,
      },
      success: function (response) {
        if (
          response.success &&
          response.data.flats &&
          response.data.flats.length > 0
        ) {
          const flats = response.data.flats;
          const dropdown = $(".scm-flat-dropdown");
          dropdown.find("option:not(:first)").remove();
          dropdown.prop("disabled", false);
          $(".scm-flat-selection .scm-help-text").hide();

          flats.forEach(function (flat) {
            dropdown.append(
              $("<option>").val(flat.id).text(escapeHtml(flat.name))
            );
          });
        } else {
          // No flats available
          const dropdown = $(".scm-flat-dropdown");
          dropdown.find("option:not(:first)").remove();
          dropdown.val("");
          dropdown.prop("disabled", true);
          $(".scm-flat-selection .scm-help-text")
            .text(
              __(
                "No flats available for this apartment",
                "service-charge-manager"
              )
            )
            .show();
        }
      },
      error: function () {
        const dropdown = $(".scm-flat-dropdown");
        dropdown.prop("disabled", true);
        $(".scm-flat-selection .scm-help-text")
          .text(__("Failed to load flats", "service-charge-manager"))
          .show();
      },
    });
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

    // Validate flatholder selections
    if (data.scm_role === "flatholder") {
      if (!data.apartment_id) {
        showModal({
          title: "Apartment Required",
          description: "Please select your apartment.",
          icon: "warning",
          buttons: [{ text: "OK", type: "primary", action: null }],
        });
        return;
      }
      if (!data.flat_id) {
        showModal({
          title: "Flat Required",
          description: "Please select your flat/unit.",
          icon: "warning",
          buttons: [{ text: "OK", type: "primary", action: null }],
        });
        return;
      }
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
  // When user clicks EDIT ICON (pencil) - Using vanilla JS
  const editIconBtn = document.getElementById("scm-edit-icon-btn");
  if (editIconBtn) {
    editIconBtn.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();
      console.log("Edit icon clicked - vanilla JS");
      document
        .querySelectorAll(".scm-value")
        .forEach((el) => (el.style.display = "none"));
      document
        .querySelectorAll(".scm-edit-field")
        .forEach((el) => (el.style.display = ""));
      this.style.display = "none";
      document.getElementById("scm-edit-actions").style.display = "";
    });
  }

  // When user clicks CANCEL - Using vanilla JS
  const cancelBtn = document.getElementById("scm-cancel-btn");
  if (cancelBtn) {
    cancelBtn.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();
      console.log("Cancel button clicked - vanilla JS");
      document
        .querySelectorAll(".scm-edit-field")
        .forEach((el) => (el.style.display = "none"));
      document
        .querySelectorAll(".scm-value")
        .forEach((el) => (el.style.display = ""));
      document.getElementById("scm-edit-actions").style.display = "none";
      document.getElementById("scm-edit-icon-btn").style.display = "";
    });
  }

  // When user clicks SAVE
  $("#scm-save-btn").on("click", function (e) {
    e.preventDefault();
    e.stopPropagation();
    console.log("Save button clicked");
    let data = {
      action: "scm_update_profile_inline",
      nonce: scmAuth.update_profile_nonce, // Make sure this is localized
    };

    // Map HTML data-key → DB keys
    const keyMapping = {
      first_name: "first_name", // WP user field
      address: "scm_address", // User meta with prefix
    };

    // Collect only allowed fields (Name and Address only)
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
          $("#scm-edit-actions").hide();
          $("#scm-edit-icon-btn").show();

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
          $("#scm-save-btn").prop("disabled", false).text("Save Changes");
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
        $("#scm-save-btn").prop("disabled", false).text("Save Changes");
      },
    });
  });

  /****************************************************
   * APARTMENT MANAGEMENT (Manager Dashboard)
   ****************************************************/

  // When user clicks ADD APARTMENT ICON (plus) - Using vanilla JS
  const addApartmentBtn = document.getElementById("scm-add-apartment-icon-btn");
  if (addApartmentBtn) {
    addApartmentBtn.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();
      console.log("Add apartment icon clicked - vanilla JS");
      // Create modal for adding apartment
      const modalConfig = {
        title: "Add New Apartment",
        description: "",
        icon: "info",
        buttons: [
          {
            text: "Cancel",
            type: "secondary",
            action: null,
            closeModal: true,
          },
          {
            text: "Add Apartment",
            type: "primary",
            action: handleAddApartmentFromModal,
          },
        ],
        customContent:
          '<form id="scm-apartment-modal-form">' +
          '<div class="scm-form-group">' +
          '<label for="scm-modal-apt-name">Apartment Name <span class="scm-required">*</span></label>' +
          '<input type="text" id="scm-modal-apt-name" name="apartment_name" required placeholder="e.g., Apartment 101">' +
          "</div>" +
          '<div class="scm-form-group">' +
          '<label for="scm-modal-apt-location">Location (Optional)</label>' +
          '<input type="text" id="scm-modal-apt-location" name="apartment_location" placeholder="e.g., Building A, Floor 3">' +
          "</div>" +
          "</form>",
      };

      showModalWithCustomContent(modalConfig);
    });
  }

  // Handle apartment form submission from modal
  function handleAddApartmentFromModal() {
    const apartmentName = $("#scm-modal-apt-name").val().trim();
    const apartmentLocation = $("#scm-modal-apt-location").val().trim();

    if (!apartmentName) {
      showModal({
        title: "Missing Information",
        description: "Please fill in the Apartment Name.",
        icon: "warning",
        buttons: [{ text: "OK", type: "primary", action: null }],
      });
      return false; // Prevent modal close
    }

    // Submit the apartment data
    $.ajax({
      url: scmAuth.ajaxurl,
      type: "POST",
      data: {
        action: "scm_add_apartment",
        nonce: scmAuth.user_nonce,
        name: apartmentName,
        location: apartmentLocation || null, // Allow null for optional location
      },
      success: function (response) {
        if (response.success) {
          showModal({
            title: "Apartment Added",
            description: "The apartment has been added successfully!",
            icon: "success",
            buttons: [{ text: "OK", type: "primary", action: null }],
          });
          loadApartments(); // Refresh list
        } else {
          showModal({
            title: "Add Failed",
            description: response.data.message || "Failed to add apartment.",
            icon: "error",
            buttons: [{ text: "OK", type: "primary", action: null }],
          });
        }
      },
      error: function () {
        showModal({
          title: "Add Error",
          description: "An error occurred while adding the apartment.",
          icon: "error",
          buttons: [{ text: "OK", type: "primary", action: null }],
        });
      },
    });
  }

  // Enhanced showModal with custom content support
  function showModalWithCustomContent(config) {
    const {
      title = "",
      description = "",
      icon = "info",
      buttons = [{ text: "OK", type: "primary", action: null }],
      customContent = "",
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
        .on("click", function (e) {
          e.preventDefault();
          if (btn.action) {
            const shouldClose = btn.action() !== false;
            if (shouldClose && btn.closeModal !== false) {
              closeModal(overlayId, modalId);
              if (onClose) onClose(true);
            }
          } else {
            closeModal(overlayId, modalId);
            if (onClose && btn.closeModal !== false) onClose(true);
          }
        });
      buttonsContainer.append(btnElem);
    });

    // Assemble modal
    const content = $("<div>").addClass("scm-modal-content");
    content.append(closeBtn, iconElem, titleElem);

    if (customContent) {
      content.append($(customContent));
    } else {
      content.append(descElem);
    }

    content.append(buttonsContainer);
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
  // for pretty date formatting
  function formatPrettyDate(dateString) {
    const date = new Date(dateString);

    const day = date.getDate();
    const month = date.toLocaleString("en-US", { month: "long" });
    const year = date.getFullYear();

    // Determine ordinal suffix
    const suffix =
      day % 10 === 1 && day !== 11
        ? "st"
        : day % 10 === 2 && day !== 12
        ? "nd"
        : day % 10 === 3 && day !== 13
        ? "rd"
        : "th";

    return `${day}${suffix} ${month} ${year}`;
  }

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
              '<div class="scm-empty-state"><p>No apartments yet. Add one using the form above.</p></div>'
            );
            return;
          }

          apartments.forEach(function (apt) {
            const createdDate = formatPrettyDate(apt.created_at);
            container.append(
              '<div class="scm-apartment-card" data-apartment-id="' +
                apt.id +
                '">' +
                '<div class="scm-apartment-card-header"><div class="scm-apartment-header-top"><h3>' +
                escapeHtml(apt.name) +
                '</h3><div class="scm-apartment-icons"><button type="button" class="scm-edit-apartment-icon-btn" data-apartment-id="' +
                apt.id +
                '" data-name="' +
                escapeAttr(apt.name) +
                '" data-location="' +
                escapeAttr(apt.location || "") +
                '" title="Edit apartment"><i class="fa-solid fa-pencil "></i></button><button type="button" class="scm-delete-apartment-icon-btn" data-apartment-id="' +
                // '" title="Edit apartment">✎</button><button type="button" class="scm-delete-apartment-icon-btn" data-apartment-id="' +
                apt.id +
                '" title="Delete apartment"><i class="fa-solid fa-trash "></i></button></div></div></div>' +
                '<div class="scm-apartment-card-body">' +
                '<div class="scm-apartment-info-row"><span class="scm-apartment-label">Location:</span><span class="scm-apartment-value">' +
                escapeHtml(apt.location || "N/A") +
                "</span></div>" +
                '<div class="scm-apartment-info-row"><span class="scm-apartment-label">Created:</span><span class="scm-apartment-value">' +
                createdDate +
                "</span></div>" +
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

  // Load apartments on dashboard page load
  if ($("#scm-apartments-tbody").length > 0) {
    loadApartments();
  }

  /****************************************************
   * APARTMENT CARD CLICK - VIEW FLATS
   ****************************************************/

  // Click on apartment card to view flats
  $(document).on("click", ".scm-apartment-card", function (e) {
    // Don't navigate if clicking on edit/delete icons
    if ($(e.target).closest(".scm-apartment-icons").length) {
      return;
    }

    const apartmentId = $(this).data("apartment-id");
    if (apartmentId) {
      // Navigate to flat-details page with apartment ID
      window.location.href =
        scmAuth.siteUrl +
        "/index.php/flat-details/?apartment_id=" +
        apartmentId;
    }
  });

  /****************************************************
   * APARTMENT EDIT & DELETE ICONS
   ****************************************************/

  // Edit apartment icon click handler
  $(document).on("click", ".scm-edit-apartment-icon-btn", function (e) {
    e.preventDefault();
    e.stopPropagation();
    console.log("Edit apartment icon clicked");

    const apartmentId = $(this).data("apartment-id");
    const apartmentName = $(this).data("name");
    const apartmentLocation = $(this).data("location");

    // Create modal for editing apartment
    const editModalConfig = {
      title: "Edit Apartment",
      description: "",
      icon: "info",
      buttons: [
        {
          text: "Cancel",
          type: "secondary",
          action: null,
          closeModal: true,
        },
        {
          text: "Save Changes",
          type: "primary",
          action: function () {
            handleEditApartmentFromModal(apartmentId);
          },
        },
      ],
      customContent:
        '<form id="scm-edit-apartment-modal-form">' +
        '<div class="scm-form-group">' +
        '<label for="scm-modal-edit-apt-name">Apartment Name <span class="scm-required">*</span></label>' +
        '<input type="text" id="scm-modal-edit-apt-name" name="apartment_name" required placeholder="e.g., Apartment 101" value="' +
        escapeAttr(apartmentName) +
        '">' +
        "</div>" +
        '<div class="scm-form-group">' +
        '<label for="scm-modal-edit-apt-location">Location (Optional)</label>' +
        '<input type="text" id="scm-modal-edit-apt-location" name="apartment_location" placeholder="e.g., Building A, Floor 3" value="' +
        escapeAttr(apartmentLocation) +
        '">' +
        "</div>" +
        "</form>",
    };

    showModalWithCustomContent(editModalConfig);
  });

  // Delete apartment icon click handler
  $(document).on("click", ".scm-delete-apartment-icon-btn", function (e) {
    e.preventDefault();
    e.stopPropagation();
    console.log("Delete apartment icon clicked");

    const apartmentId = $(this).data("apartment-id");
    const apartmentCard = $(this).closest(".scm-apartment-card");

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
            handleDeleteApartment(apartmentId);
          },
        },
      ],
    });
  });

  // Handle apartment edit submission from modal
  function handleEditApartmentFromModal(apartmentId) {
    const apartmentName = $("#scm-modal-edit-apt-name").val().trim();
    const apartmentLocation = $("#scm-modal-edit-apt-location").val().trim();

    if (!apartmentName) {
      showModal({
        title: "Missing Information",
        description: "Please enter the apartment name.",
        icon: "warning",
        buttons: [{ text: "OK", type: "primary", action: null }],
      });
      return;
    }

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
      beforeSend: function () {
        console.log("Updating apartment:", apartmentId);
      },
      success: function (response) {
        if (response.success) {
          showModal({
            title: "Apartment Updated",
            description: "The apartment has been updated successfully!",
            icon: "success",
            buttons: [{ text: "OK", type: "primary", action: null }],
          });
          // Close modal and reload apartments
          $(".scm-modal-overlay").remove();
          loadApartments();
        } else {
          showModal({
            title: "Update Failed",
            description: response.data.message || "Failed to update apartment.",
            icon: "error",
            buttons: [{ text: "OK", type: "primary", action: null }],
          });
        }
      },
      error: function () {
        showModal({
          title: "Update Error",
          description: "An error occurred while updating the apartment.",
          icon: "error",
          buttons: [{ text: "OK", type: "primary", action: null }],
        });
      },
    });
  }

  // Handle apartment delete
  function handleDeleteApartment(apartmentId) {
    $.ajax({
      url: scmAuth.ajaxurl,
      type: "POST",
      data: {
        action: "scm_delete_apartment",
        nonce: scmAuth.user_nonce,
        apartment_id: apartmentId,
      },
      beforeSend: function () {
        console.log("Deleting apartment:", apartmentId);
      },
      success: function (response) {
        if (response.success) {
          showModal({
            title: "Deleted",
            description: "The apartment has been deleted successfully!",
            icon: "success",
            buttons: [{ text: "OK", type: "primary", action: null }],
          });
          loadApartments(); // Refresh the list
        } else {
          showModal({
            title: "Delete Failed",
            description: response.data.message || "Failed to delete apartment.",
            icon: "error",
            buttons: [{ text: "OK", type: "primary", action: null }],
          });
        }
      },
      error: function () {
        showModal({
          title: "Delete Error",
          description: "An error occurred while deleting the apartment.",
          icon: "error",
          buttons: [{ text: "OK", type: "primary", action: null }],
        });
      },
    });
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

  /****************************************************
   * FLAT MANAGEMENT (Flat Details Page)
   ****************************************************/

  // Load flats when page loads
  if (
    $("#scm-flats-tbody").length > 0 &&
    typeof window.scmApartmentId !== "undefined"
  ) {
    loadFlats();
  }

  // Load flats via AJAX
  function loadFlats() {
    const apartmentId = window.scmApartmentId;

    $.ajax({
      url: scmAuth.ajaxurl,
      type: "POST",
      data: {
        action: "scm_get_flats",
        nonce: scmAuth.user_nonce,
        apartment_id: apartmentId,
      },
      success: function (response) {
        if (response.success && response.data.flats) {
          const flats = response.data.flats;
          const container = $("#scm-flats-tbody");
          container.empty();

          if (flats.length === 0) {
            container.append(
              '<tr><td colspan="3" class="scm-empty-state"><p>No flats yet. Add one using the + button above.</p></td></tr>'
            );
            return;
          }

          flats.forEach(function (flat) {
            // Display holder name if available, otherwise show "-"
            const holderName = flat.display_name
              ? escapeHtml(flat.display_name)
              : "-";
            container.append(
              '<tr data-flat-id="' +
                flat.id +
                '">' +
                "<td>" +
                escapeHtml(flat.name) +
                "</td>" +
                "<td>" +
                escapeHtml(flat.floor_number || "-") +
                "</td>" +
                "<td>" +
                holderName +
                "</td>" +
                "</tr>"
            );
          });
        } else {
          $("#scm-flats-tbody").html(
            '<tr><td colspan="3" class="scm-error-state"><p>Failed to load flats.</p></td></tr>'
          );
        }
      },
      error: function () {
        $("#scm-flats-tbody").html(
          '<tr><td colspan="3" class="scm-error-state"><p>Error loading flats.</p></td></tr>'
        );
      },
    });
  }

  // Add flat button click handler
  $(document).on("click", "#scm-add-flat-icon-btn", function (e) {
    e.preventDefault();
    e.stopPropagation();
    console.log("Add flat icon clicked");

    const addFlatModalConfig = {
      title: "Add New Flat",
      description: "",
      icon: "info",
      buttons: [
        {
          text: "Cancel",
          type: "secondary",
          action: null,
          closeModal: true,
        },
        {
          text: "Add Flat",
          type: "primary",
          action: handleAddFlatFromModal,
        },
      ],
      customContent:
        '<form id="scm-flat-modal-form">' +
        '<div class="scm-form-group">' +
        '<label for="scm-modal-flat-name">Flat Name <span class="scm-required">*</span></label>' +
        '<input type="text" id="scm-modal-flat-name" name="flat_name" required placeholder="e.g., Flat 101">' +
        "</div>" +
        '<div class="scm-form-group">' +
        '<label for="scm-modal-flat-floor">Floor Number</label>' +
        '<input type="text" id="scm-modal-flat-floor" name="flat_floor" placeholder="e.g., 1st, 2nd, Ground">' +
        "</div>" +
        "</form>",
    };

    showModalWithCustomContent(addFlatModalConfig);
  });

  // Handle flat form submission from modal
  function handleAddFlatFromModal() {
    const flatName = $("#scm-modal-flat-name").val().trim();
    const flatFloor = $("#scm-modal-flat-floor").val().trim();
    const apartmentId = window.scmApartmentId;

    if (!flatName) {
      showModal({
        title: "Missing Information",
        description: "Please enter the flat name.",
        icon: "warning",
        buttons: [{ text: "OK", type: "primary", action: null }],
      });
      return;
    }

    $.ajax({
      url: scmAuth.ajaxurl,
      type: "POST",
      data: {
        action: "scm_add_flat",
        nonce: scmAuth.user_nonce,
        apartment_id: apartmentId,
        name: flatName,
        floor_number: flatFloor,
      },
      beforeSend: function () {
        console.log("Adding flat:", flatName);
      },
      success: function (response) {
        if (response.success) {
          showModal({
            title: "Flat Added",
            description: "The flat has been added successfully!",
            icon: "success",
            buttons: [{ text: "OK", type: "primary", action: null }],
          });
          // Close modal and reload flats
          $(".scm-modal-overlay").remove();
          loadFlats();
        } else {
          showModal({
            title: "Add Failed",
            description: response.data.message || "Failed to add flat.",
            icon: "error",
            buttons: [{ text: "OK", type: "primary", action: null }],
          });
        }
      },
      error: function () {
        showModal({
          title: "Add Error",
          description: "An error occurred while adding the flat.",
          icon: "error",
          buttons: [{ text: "OK", type: "primary", action: null }],
        });
      },
    });
  }

  // Back to apartments button
  $(document).on("click", "#scm-back-to-apartments-btn", function (e) {
    e.preventDefault();
    e.stopPropagation();
    window.location.href = "index.php/dashboard";
  });
});
