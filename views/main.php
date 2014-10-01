<?php

if (!empty($message)) {
	$html.= '<div class="alert alert-' . $message['type'] . '">' . $message['message'] . '</div>';
}

echo $html;

?>
