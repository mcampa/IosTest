<?php
/*
 * @author Michael Härtl <haertl.mike@gmail.com> (sponsored by PeoplePerHour.com)
 * @modified by Mario Campa <mcampa@gmail.com>
 * @version 1.1.5-dev
 * @license http://www.opensource.org/licenses/MIT
 */
namespace Application\Controller\Plugin;
 
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class xhtml2pdf extends AbstractPlugin {

    // protected $bin = '/usr/bin/wkhtmltopdf';
    protected $bin = '/usr/local/bin/wkhtmltopdf';

    protected $enableEscaping = true;

	protected $options     = array();
	protected $pageOptions = array();
	protected $objects     = array();

    protected $tmp;
    protected $tmpFile;
    protected $tmpFiles = array();

    protected $error;

    // Regular expression to detect HTML strings
    const REGEX_HTML = '/<html/i';

    /**
     * @param array $options global options for wkhtmltopdf (optional)
     */
    public function __construct($options=array())
    {
        if($options!==array())
            $this->setOptions($options);
    }

    /**
     * Remove temporary PDF file and pages when script completes
     */
    public function __destruct()
    {
        if($this->tmpFile!==null)
            unlink($this->tmpFile);

        foreach($this->tmpFiles as $tmp)
            unlink($tmp);
    }

    /**
     * Add a page object to the output
     *
     * @param string $input either a URL, a HTML string or a PDF/HTML filename
     * @param array $options optional options for this page
     */
    public function addPage($input,$options=array())
    {
        $options['input'] = preg_match(self::REGEX_HTML, $input) ? $this->createTmpFile($input) : $input;
        $this->objects[] = array_merge($this->pageOptions,$options);
    }

    /**
     * Add a cover page object to the output
     *
     * @param string $input either a URL or a PDF filename
     * @param array $options optional options for this page
     */
    public function addCover($input,$options=array())
    {
        $options['input'] = "cover $input";
        $this->objects[] = array_merge($this->pageOptions,$options);
    }

    /**
     * Add a TOC object to the output
     *
     * @param array $options optional options for the table of contents
     */
    public function addToc($options=array())
    {
        $options['input'] = "toc";
        $this->objects[] = $options;
    }

    /**
     * Save the PDF to given filename (triggers PDF creation)
     *
     * @param string $filename to save PDF as
     * @return bool wether PDF was created successfully
     */
    public function saveAs($filename)
    {
        if(($pdfFile = $this->getPdfFilename())===false)
            return false;

        copy($pdfFile,$filename);
        return true;
    }

    /**
     * Send PDF to client, either inline or as download (triggers PDF creation)
     *
     * @param mixed $filename the filename to send. If empty, the PDF is streamed.
     * @return bool wether PDF was created successfully
     */
    public function send($filename=null)
    {
        if(($pdfFile = $this->getPdfFilename())===false)
            return false;

        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Type: application/pdf');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: '.filesize($pdfFile));

        if($filename!==null)
            header("Content-Disposition: attachment; filename=\"$filename\"");

        readfile($pdfFile);
        return true;
    }

    /**
     * Set global option(s)
     *
     * @param array $options list of global options to set as name/value pairs
     */
    public function setOptions($options)
    {
        foreach($options as $key=>$val)
            if($key==='bin')
                $this->bin = $val;
            elseif($key==='tmp')
                $this->tmp = $val;
            elseif($key==='enableEscaping')
                $this->enableEscaping = (bool)$val;
            elseif(is_int($key))
                $this->options[] = $val;
            else
                $this->options[$key] = $val;
    }

    /**
     * @param array $options that should be applied to all pages as name/value pairs
     */
    public function setPageOptions($options=array())
    {
        $this->pageOptions = $options;
    }

    /**
     * @return mixed the detailled error message including the wkhtmltopdf command or null if none
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @return string path to temp directory
     */
    public function getTmpDir()
    {
        if($this->tmp===null)
            $this->tmp = sys_get_temp_dir();

        return $this->tmp;
    }

    /**
     * @param string $filename the filename of the output file
     * @return string the wkhtmltopdf command string
     */
    public function getCommand($filename)
    {
        $command = $this->enableEscaping ? escapeshellarg($this->bin) : $this->bin;

        $command .= $this->renderOptions($this->options);

        foreach($this->objects as $object)
        {
            $command .= ' '.$object['input'];
            unset($object['input']);
            $command .= $this->renderOptions($object);
        }

        return $command.' '.$filename;
    }

    /**
     * @return mixed the temporary PDF filename or false on error (triggers PDf creation)
     */
    protected function getPdfFilename()
    {
        if($this->tmpFile===null)
        {
            $tmpFile = tempnam($this->getTmpDir(),'tmp_WkHtmlToPdf_');

            if($this->createPdf($tmpFile)===true)
                $this->tmpFile = $tmpFile;
            else
                return false;
        }

        return $this->tmpFile;
    }

    /**
     * Create the temporary PDF file
     */
    protected function createPdf($fileName)
    {
        $command = $this->getCommand($fileName);

        // we use proc_open with pipes to fetch error output
        $descriptors = array(
            2   => array('pipe','w'),
        );
        $process = proc_open($command, $descriptors, $pipes, null, null, array('bypass_shell'=>true));

        if(is_resource($process)) {

            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            $result = proc_close($process);

            if($result!==0)
            {
                if (!file_exists($fileName) || filesize($fileName)===0)
                    $this->error = "Could not run command $command:\n$stderr";
                else
                    $this->error = "Warning: an error occured while creating the PDF.\n$stderr";
            }
        } else
            $this->error = "Could not run command $command";

        return $this->error===null;
    }

    /**
     * Create a tmp file with given content
     *
     * @param string $content the file content
     * @return string the path to the created file
     */
    protected function createTmpFile($content)
    {
        $tmpFile = tempnam($this->getTmpDir(),'tmp_WkHtmlToPdf_');
        rename($tmpFile, ($tmpFile.='.html'));
        file_put_contents($tmpFile, $content);

        $this->tmpFiles[] = $tmpFile;

        return $tmpFile;
    }

    /**
     * @param array $options for a wkhtml, either global or for an object
     * @return string the string with options
     */
    protected function renderOptions($options)
    {
        $out = '';
        foreach($options as $key=>$val)
            if(is_numeric($key))
                $out .= " --$val";
            else
                $out .= " --$key ".($this->enableEscaping ? escapeshellarg($val) : $val);

        return $out;
    }
}
