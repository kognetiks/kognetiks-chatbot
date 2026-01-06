jQuery(document).ready(function($) {
    if (window.location.href.indexOf('page=chatbot-chatgpt') > -1) {
        if ($('a.nav-tab-active').text() === 'Support' ||
            $('a.nav-tab-active').text() === 'Tools' ||
            $('a.nav-tab-active').text() === 'Insights' ||
            $('a.nav-tab-active').text() === 'Insights') {
            $('input[type="submit"]').hide();
        }
    }
});