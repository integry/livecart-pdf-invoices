<?php

class PdfInvoiceLink extends ViewPlugin
{
	public function process($html)
	{
		return preg_replace('/<ul class="menu">(.*)<\/ul>/msU', '<ul class="menu">\\1{include file="module/pdf-invoice/menu.tpl"}</ul>', $html);
	}
}

?>