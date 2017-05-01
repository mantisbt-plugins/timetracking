<?php

# Prevent output of HTML in the content if errors occur
define( 'DISABLE_INLINE_ERROR_REPORTING', true );

/**
 * Print Language translation for javascript
 * @param string $p_lang_key Language string being translated.
 * @return void
 */
function print_translation( $p_lang_key ) {
	echo "timetracking_translations['" . $p_lang_key . "'] = '" . addslashes( plugin_lang_get( $p_lang_key ) ) . "';\n";
}

# Send correct MIME Content-Type header for JavaScript content.
# See http://www.rfc-editor.org/rfc/rfc4329.txt for details on why
# application/javasscript is the correct MIME type.
header( 'Content-Type: application/javascript; charset=UTF-8' );

# Don't let Internet Explorer second-guess our content-type, as per
# http://blogs.msdn.com/b/ie/archive/2008/07/02/ie8-security-part-v-comprehensive-protection.aspx
header( 'X-Content-Type-Options: nosniff' );

echo "var timetracking_translations = new Array();\n";
print_translation( 'start' );
print_translation( 'stop' );
print_translation( 'resume' );
print_translation( 'reset' );