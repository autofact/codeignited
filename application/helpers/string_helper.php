<?php

function starts_with($haystack, $needle) {
	return $needle === "" || strpos($haystack, $needle) === 0;
}

function ends_with($haystack, $needle) {
	return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
}
