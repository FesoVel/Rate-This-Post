jQuery(document).ready(function($) {
    // Click event on the button
    $('body').on('click', '.rtp-vote-btn', function(e) {
        e.preventDefault();

        var $this = $(this);
        var post_id = $this.data('post-id');
        var vote = $this.data('vote');
        var nonce = $this.data('nonce');
        var has_voted = $('#rtp-vote-buttons').data('has-voted');

        if (!has_voted){
            // Start ajax call
            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: rtp_ajax_obj.ajax_url, // object is set in class-votethispost:enqueue_scripts()
                data: {
                    action: 'rtp_vote',
                    post_id: post_id,
                    vote: vote,
                    security: nonce,
                },
                success: function(response) {
                    var total_votes = response.data['total_votes'];
                    var no_votes = response.data['no_votes'];
                    var yes_votes = response.data['yes_votes'];
                    var yes_icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="#999999" width="25px" height="25px" viewBox="0 0 256 256" id="Flat"><path d="M128,24A104,104,0,1,0,232,128,104.12041,104.12041,0,0,0,128,24Zm36,72a12,12,0,1,1-12,12A12.0006,12.0006,0,0,1,164,96ZM92,96a12,12,0,1,1-12,12A12.0006,12.0006,0,0,1,92,96Zm84.50488,60.00293a56.01609,56.01609,0,0,1-97.00976.00049,8.00016,8.00016,0,1,1,13.85058-8.01074,40.01628,40.01628,0,0,0,69.30957-.00049,7.99974,7.99974,0,1,1,13.84961,8.01074Z"/></svg>';
                    var no_icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="#999999" width="25px" height="25px" viewBox="0 0 256 256" id="Flat"><path d="M128,24A104,104,0,1,0,232,128,104.12041,104.12041,0,0,0,128,24ZM92,96a12,12,0,1,1-12,12A12.0006,12.0006,0,0,1,92,96Zm76,72H88a8,8,0,0,1,0-16h80a8,8,0,0,1,0,16Zm-4-48a12,12,0,1,1,12-12A12.0006,12.0006,0,0,1,164,120Z"/></svg>';

                    if (response.success) {
                        // Outpout sucessfull message
                        $('#rtp-vote-buttons .rtp-title').text('Thank you for your feedback.');

                        var yes_percentage = total_votes > 0 ? parseInt((yes_votes / total_votes) * 100) : 0;
                        var no_percentage = 100 - yes_percentage;

                        // Add icon + percentage + active class
                        if (vote == "Yes"){
                            $('.rtp-vote-btn-yes').html(yes_icon + ' ' + yes_percentage + '%');
                            $('.rtp-vote-btn-yes').addClass('rtp-active');
                            $('.rtp-vote-btn-no').html(no_icon + ' ' + no_percentage + '%');
                        }
                        else{
                            $('.rtp-vote-btn-no').html(no_icon + ' ' + no_percentage + '%');
                             $('.rtp-vote-btn-no').addClass('rtp-active');
                             $('.rtp-vote-btn-yes').html(yes_icon + ' ' + yes_percentage + '%');
                        }

                    } else {
                        // Display error message & hide buttons
                        $('.rtp-btn-wrap').hide();
                        $('.rtp-title').text('Something went wrong. Please try again.');
                    }
                },
                error: function() {
                    // Display error message & hide buttons
                    $('.rtp-btn-wrap').hide();
                    $('.rtp-title').text('Something went wrong. Please try again.');
                }
            });
        }
    });
});
