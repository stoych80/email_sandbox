jQuery(function ($) {
	if (!$('#dd_email_sandbox_disable_sending_out_emails').is(':checked')) {
		$('.row.dd-email-sandbox-enable-email').show();
		if ($('#dd_email_sandbox_enable_email_sandbox').is(':checked')) {
			$('.row.dd-email-sandbox-emails, .row.dd-email-sandbox-exclude-emails').show();
		}
	}
	$('#dd_email_sandbox_disable_sending_out_emails').click(function (e) {
		if (!$(this).is(':checked')) {
			$('.row.dd-email-sandbox-enable-email').slideDown();
			if ($('#dd_email_sandbox_enable_email_sandbox').is(':checked')) {
				$('.row.dd-email-sandbox-emails, .row.dd-email-sandbox-exclude-emails').slideDown();
			} else {
				$('.row.dd-email-sandbox-emails, .row.dd-email-sandbox-exclude-emails').slideUp();
			}
		} else {
			$('.row.dd-email-sandbox-enable-email, .row.dd-email-sandbox-emails, .row.dd-email-sandbox-exclude-emails').slideUp();
		}
	});
	$('#dd_email_sandbox_enable_email_sandbox').click(function (e) {
		if ($(this).is(':checked')) {
			$('.row.dd-email-sandbox-emails, .row.dd-email-sandbox-exclude-emails').slideDown();
		} else {
			$('.row.dd-email-sandbox-emails, .row.dd-email-sandbox-exclude-emails').slideUp();
		}
	});
	$('img.icon_information').tooltip({
      show: {
        effect: "slideDown",
        delay: 250
      }
    });
});
