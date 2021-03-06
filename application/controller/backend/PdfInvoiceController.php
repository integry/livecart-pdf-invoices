<?php

ClassLoader::import("application.controller.backend.abstract.StoreManagementController");

/**
 * @author Integry Systems
 * @package application.controller
 */
class PdfInvoiceController extends StoreManagementController
{
	public function invoice()
	{
		// disable output compression
		unset($_SERVER['HTTP_ACCEPT_ENCODING']);

		ini_set('memory_limit', '128M');
		$dir = ClassLoader::getRealPath('module.pdf-invoice.library.html2ps.');
		require_once($dir . 'config.inc.php');
		require_once($dir . 'pipeline.factory.class.php');
		parse_config_file($dir . 'html2ps.config');

		require_once ClassLoader::getRealPath('module.pdf-invoice.application.helper.pdf-funcs') . '.php';
		$this->request->set('controller', 'backend.customerOrder');
		$this->request->set('action', 'printInvoice');
		ob_start();
		$this->application->run(true);
		$html = ob_get_contents();
		ob_clean();

		$tmp = md5('invoice-' . $this->request->get('id') . '-' . time());
		$dir = ClassLoader::getRealPath('cache.pdf.');
		if (!file_exists($dir))
		{
			mkdir($dir);
		}

		$html = str_replace('class="subTotalCaption', 'style="text-align: right;" class="subTotalCaption', $html);
		$html = str_replace('<body>', '<body class="pdf">', $html);

		file_put_contents($dir . $tmp, $html);
		$this->convert_to_pdf($dir . $tmp, $dir . $tmp);

		$order = CustomerOrder::getInstanceByID($this->request->get('id'));
		$fileName = $order->invoiceNumber->get() . '.pdf';
		$fileName = preg_replace('/[^0-9a-z\.\_\-]/i','-', $fileName);

		$response = new ObjectFileResponse(ObjectFile::getNewInstance('ObjectFile', $dir . $tmp, $fileName));
		$response->deleteFileOnComplete();

		return $response;
	}

	private function convert_to_pdf($path_to_html, $path_to_pdf)
	{
		$pipeline = PipelineFactory::create_default_pipeline("", // Attempt to auto-detect encoding
														   "");
		// Override HTML source
		$pipeline->fetchers[] = new MyFetcherLocalFile($path_to_html);

		// Override destination to local file
		$pipeline->destination = new MyDestinationFile($path_to_pdf);

		$baseurl = "";
		$media = Media::predefined("A4");
		$media->set_landscape(false);
		$media->set_margins(array('left'   => 0,
								'right'  => 0,
								'top'    => 10,
								'bottom' => 10));
		$media->set_pixels(1024);

		global $g_config;
		$g_config = array(
						'cssmedia'     => 'screen',
						'scalepoints'  => '1',
						'renderimages' => true,
						'renderlinks'  => false,
						'renderfields' => true,
						'renderforms'  => false,
						'mode'         => 'html',
						'encoding'     => '',
						'debugbox'     => false,
						'pdfversion'    => '1.4',
						'draw_page_border' => false
						);
		$pipeline->configure($g_config);
		$pipeline->process($baseurl, $media);
	}
}

?>