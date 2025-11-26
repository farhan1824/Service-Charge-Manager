jQuery(document).ready(function ($) {
  function showMessage(message, type = "info", debug = null) {
    const resultDiv = $("#scm-assign-result");
    let html = `<div class="notice notice-${type}"><p>${message}</p></div>`;

    if (debug && scmAdmin.debug) {
      html += `<div class="dev-message"><pre>${JSON.stringify(
        debug,
        null,
        2
      )}</pre></div>`;
    }

    resultDiv.html(html).show();
  }

  // Handle manager assignment
  $("#scm-assign-manager-form").on("submit", function (e) {
    e.preventDefault();
    const phone = $("#scm-phone").val().trim();
    const district = $("#scm-district").val().trim();
    const submitBtn = $("#scm-assign-manager");

    if (!phone || !district) {
      showMessage("Please enter both phone and district", "error");
      return;
    }

    // Phone number validation
    const phonePattern = /^(\+8801|8801|01)[0-9]{9}$/;
    if (!phonePattern.test(phone)) {
      showMessage("Please enter a valid Bangladesh phone number", "error");
      return;
    }

    submitBtn.prop("disabled", true).text("Promoting to Manager...");

    $.post(scmAdmin.ajaxurl, {
      action: "scm_assign_manager",
      nonce: scmAdmin.nonce,
      phone: phone,
      district: district,
    })
      .done(function (response) {
        if (response.success) {
          showMessage(
            response.data.message,
            "success",
            response.data.dev_debug
          );
          setTimeout(function () {
            location.reload();
          }, 1500);
        } else {
          showMessage(response.data.message, "error", response.data.dev_debug);
        }
      })
      .fail(function (xhr) {
        showMessage("Request failed. Please try again.", "error", {
          status: xhr.status,
          statusText: xhr.statusText,
        });
      })
      .always(function () {
        submitBtn.prop("disabled", false).text("Promote to Manager");
      });
  });

  // Handle manager revocation
  // Handle promote button in user list
  $(".promote-to-manager").on("click", function (e) {
    e.preventDefault();
    const btn = $(this);
    const phone = btn.data("phone");
    const name = btn.data("name");

    if (!confirm(`Are you sure you want to promote ${name} to manager?`)) {
      return;
    }

    btn.prop("disabled", true).text("Promoting...");

    // Prompt for district
    const district = prompt("Enter the district for this manager:");
    if (!district) {
      btn.prop("disabled", false).text("Promote to Manager");
      return;
    }

    $.post(scmAdmin.ajaxurl, {
      action: "scm_assign_manager",
      nonce: scmAdmin.nonce,
      phone: phone,
      district: district,
    })
      .done(function (response) {
        if (response.success) {
          showMessage(
            response.data.message,
            "success",
            response.data.dev_debug
          );
          setTimeout(function () {
            location.reload();
          }, 1500);
        } else {
          showMessage(response.data.message, "error", response.data.dev_debug);
          btn.prop("disabled", false).text("Promote to Manager");
        }
      })
      .fail(function (xhr) {
        showMessage("Request failed. Please try again.", "error", {
          status: xhr.status,
          statusText: xhr.statusText,
        });
        btn.prop("disabled", false).text("Promote to Manager");
      });
  });

  // Handle revoke button
  $(".scm-revoke-manager").on("click", function (e) {
    e.preventDefault();
    const btn = $(this);
    const userId = btn.data("user-id");

    if (
      !confirm("Are you sure you want to revoke manager access for this user?")
    ) {
      return;
    }

    btn.prop("disabled", true).text("Revoking...");

    $.post(scmAdmin.ajaxurl, {
      action: "scm_revoke_manager",
      nonce: btn.data("nonce"),
      user_id: userId,
    })
      .done(function (response) {
        if (response.success) {
          showMessage(
            response.data.message,
            "success",
            response.data.dev_debug
          );
          setTimeout(function () {
            location.reload();
          }, 1500);
        } else {
          showMessage(response.data.message, "error", response.data.dev_debug);
          btn.prop("disabled", false).text("Revoke Manager");
        }
      })
      .fail(function (xhr) {
        showMessage("Request failed. Please try again.", "error");
        btn.prop("disabled", false).text("Revoke Manager");
      });
  });

  // Filter users by district for managers
  if (scmAdmin.isManager && scmAdmin.managerDistrict) {
    $("table.wp-list-table tbody tr").each(function () {
      const district = $(this).find("td:eq(3)").text().trim();
      if (district !== scmAdmin.managerDistrict) {
        $(this).hide();
      }
    });
  }
});
