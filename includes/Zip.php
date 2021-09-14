<?php

namespace TheLion\UseyourDrive;

class Zip
{
    /**
     * Unique ID.
     *
     * @var string
     */
    public $request_id;

    /**
     * Name of the zip file.
     *
     * @var string
     */
    public $zip_name;
    /**
     * Files that need to be added to ZIP.
     *
     * @var array
     */
    public $files = [];

    /**
     * Folders that need to be created in ZIP.
     *
     * @var array
     */
    public $folders = [];

    /**
     * Number of bytes that are downloaded so far.
     *
     * @var int
     */
    public $bytes_so_far = 0;

    /**
     * Bytes that need to be download in total.
     *
     * @var int
     */
    public $bytes_total = 0;

    /**
     * Current status.
     *
     * @var string
     */
    public $current_action = 'starting';

    /**
     * Message describing the current status.
     *
     * @var string
     */
    public $current_action_str = '';

    /**
     * @var \TheLion\UseyourDrive\CacheNode[]
     */
    public $entries_downloaded = [];
    /**
     * @var \TheLion\UseyourDrive\Client
     */
    private $_client;

    /**
     * @var \TheLion\UseyourDrive\Processor
     */
    private $_processor;

    /**
     * @var \ZipStream\ZipStream
     */
    private $_zip_handler;

    public function __construct(Processor $_processor = null, $request_id)
    {
        $this->_client = $_processor->get_client();
        $this->_processor = $_processor;
        $this->request_id = $request_id;
    }

    /**
     * Main function creating the ZIP file for files and folders which are requested via $_REQUEST.
     */
    public function do_zip()
    {
        $this->initialize();
        $this->current_action = 'indexing';
        $this->current_action_str = esc_html__('Selecting files...', 'wpcloudplugins');

        $this->index();
        $this->create();

        $this->add_folders();

        $this->current_action = 'downloading';
        $this->add_files();

        $this->current_action = 'finalizing';
        $this->current_action_str = esc_html__('Almost ready', 'wpcloudplugins');
        $this->set_progress();
        $this->finalize();

        $this->current_action = 'finished';
        $this->current_action_str = esc_html__('Finished', 'wpcloudplugins');
        $this->set_progress();

        exit();
    }

    /**
     * Load the ZIP library and make sure that the root folder is loaded.
     */
    public function initialize()
    {
        ignore_user_abort(false);

        require_once USEYOURDRIVE_ROOTDIR.'/vendors/ZipStream/vendor/autoload.php';

        // Check if file/folder is cached and still valid
        $cachedfolder = $this->get_client()->get_folder();

        if (false === $cachedfolder || false === $cachedfolder['folder']) {
            return new \WP_Error('broke', esc_html__("Requested directory isn't allowed", 'wpcloudplugins'));
        }

        $folder = $cachedfolder['folder']->get_entry();

        // Check if entry is allowed
        if (!$this->get_processor()->_is_entry_authorized($cachedfolder['folder'])) {
            return new \WP_Error('broke', esc_html__("Requested directory isn't allowed", 'wpcloudplugins'));
        }

        $this->zip_name = basename($folder->get_name()).'_'.time().'.zip';

        $this->set_progress();

        // Stop WP from buffering
        if (ob_get_level() > 0) {
            ob_end_clean();
        } else {
            flush();
        }
    }

    /**
     * Create the ZIP File.
     */
    public function create()
    {
        $options = new \ZipStream\Option\Archive();
        $options->setSendHttpHeaders(true);
        $options->setFlushOutput(true);
        $options->setContentType('application/octet-stream');
        header('X-Accel-Buffering: no');

        // create a new zipstream object
        $this->_zip_handler = new \ZipStream\ZipStream(\TheLion\UseyourDrive\Helpers::filter_filename($this->zip_name), $options);
    }

    /**
     * Create a list of files and folders that need to be zipped.
     */
    public function index()
    {
        $requested_ids = [$this->get_processor()->get_requested_entry()];

        if (isset($_REQUEST['files'])) {
            $requested_ids = $_REQUEST['files'];
        }

        foreach ($requested_ids as $fileid) {
            $cached_entry = $this->get_client()->get_entry($fileid);

            if (false === $cached_entry) {
                continue;
            }

            $data = $this->get_client()->_get_files_recursive($cached_entry);

            $this->files = array_merge($this->files, $data['files']);
            $this->folders = array_merge($this->folders, $data['folders']);
            $this->bytes_total += $data['bytes_total'];

            $this->current_action_str = esc_html__('Selecting files...', 'wpcloudplugins').' ('.count($this->files).')';
            $this->set_progress();
        }
    }

    /**
     * Add Folders to Zip file.
     */
    public function add_folders()
    {
        if (count($this->folders) > 0) {
            foreach ($this->folders as $key => $folder) {
                $this->_zip_handler->addFile($folder, '');
                unset($this->folders[$key]);
            }
        }
    }

    /**
     * Add all requests files to Zip file.
     */
    public function add_files()
    {
        if (count($this->files) > 0) {
            foreach ($this->files as $key => $file) {
                $this->add_file_to_zip($file);

                unset($this->files[$key]);

                $cached_entry = $this->get_client()->get_cache()->get_node_by_id($file['ID']);
                $this->entries_downloaded[] = $cached_entry;

                do_action('useyourdrive_log_event', 'useyourdrive_downloaded_entry', $cached_entry, ['as_zip' => true]);

                $this->current_action_str = esc_html__('Downloading...', 'wpcloudplugins').'<br/>('.Helpers::bytes_to_size_1024($this->bytes_so_far).' / '.Helpers::bytes_to_size_1024($this->bytes_total).')';
                $this->set_progress();
            }
        }
    }

    /**
     * Download the request file and add it to the ZIP.
     *
     * @param array $file
     */
    public function add_file_to_zip($file)
    {
        @set_time_limit(0);

        // get file
        $cached_entry = $this->get_client()->get_cache()->get_node_by_id($file['ID']);
        $download_stream = fopen('php://temp/maxmemory:'.(5 * 1024 * 1024), 'r+');

        $request = new \UYDGoogle_Http_Request($file['url'], 'GET');
        $this->get_client()->get_library()->getIo()->setOptions(
            [
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_FILE => $download_stream,
                CURLOPT_HEADER => false,
                CURLOPT_CONNECTTIMEOUT => 900,
                CURLOPT_TIMEOUT => 900,
            ]
        );

        try {
            $this->get_client()->get_library()->getAuth()->authenticatedRequest($request);
            curl_close($this->get_client()->get_library()->getIo()->getHandler());
        } catch (\Exception $ex) {
            fclose($download_stream);
            error_log('[WP Cloud Plugin message]: '.sprintf('API Error on line %s: %s', __LINE__, $ex->getMessage()));

            return;
        }

        rewind($download_stream);

        $this->bytes_so_far += $file['bytes'];

        $fileOptions = new \ZipStream\Option\File();
        if (!empty($cached_entry->get_entry()->get_last_edited())) {
            $date = new \DateTime();
            $date->setTimestamp(strtotime($cached_entry->get_entry()->get_last_edited()));
            $fileOptions->setTime($date);
        }
        $fileOptions->setComment((string) $cached_entry->get_entry()->get_description());

        try {
            $this->_zip_handler->addFileFromStream(trim($file['path'], '/'), $download_stream, $fileOptions);
        } catch (\Exception $ex) {
            error_log('[WP Cloud Plugin message]: '.sprintf('Error creating ZIP file %s: %s', __LINE__, $ex->getMessage()));

            $this->current_action = 'failed';
            $this->set_progress();

            exit();
        }

        fclose($download_stream);
    }

    /**
     * Finalize the zip file.
     */
    public function finalize()
    {
        $this->set_progress();

        // Close zip
        $result = $this->_zip_handler->finish();

        // Send email if needed
        if ('1' === $this->get_processor()->get_shortcode_option('notificationdownload')) {
            $this->get_processor()->send_notification_email('download', $this->entries_downloaded);
        }

        // Download Zip Hook
        do_action('useyourdrive_download_zip', $this->entries_downloaded);
    }

    /**
     * Received progress information for the ZIP process from database.
     *
     * @param string $request_id
     */
    public static function get_progress($request_id)
    {
        return get_transient('useyourdrive_zip_'.substr($request_id, 0, 40));
    }

    /**
     * Set current progress information for ZIP process in database.
     */
    public function set_progress()
    {
        $status = [
            'id' => $this->request_id,
            'status' => [
                'bytes_so_far' => $this->bytes_so_far,
                'bytes_total' => $this->bytes_total,
                'percentage' => ($this->bytes_total > 0) ? (round(($this->bytes_so_far / $this->bytes_total) * 100)) : 0,
                'progress' => $this->current_action,
                'progress_str' => $this->current_action_str,
            ],
        ];

        // Update progress
        return set_transient('useyourdrive_zip_'.substr($this->request_id, 0, 40), $status, HOUR_IN_SECONDS);
    }

    /**
     * Get progress information for the ZIP process
     * Used to display a progress percentage on Front-End.
     *
     * @param string $request_id
     */
    public static function get_status($request_id)
    {
        // Try to get the upload status of the file
        for ($_try = 1; $_try < 6; ++$_try) {
            $result = self::get_progress($request_id);

            if (false !== $result) {
                if ('failed' === $result['status']['progress'] || 'finished' === $result['status']['progress']) {
                    delete_transient('useyourdrive_zip_'.substr($request_id, 0, 40));
                }

                break;
            }

            // Wait a moment, perhaps the upload still needs to start
            usleep(500000 * $_try);
        }

        if (false === $result) {
            $result = ['file' => false, 'status' => ['bytes_down_so_far' => 0, 'total_bytes_down_expected' => 0, 'percentage' => 0, 'progress' => 'failed']];
        }

        echo json_encode($result);

        exit();
    }

    /**
     * @return \TheLion\UseyourDrive\Processor
     */
    public function get_processor()
    {
        return $this->_processor;
    }

    /**
     * @return \TheLion\UseyourDrive\Client
     */
    public function get_client()
    {
        return $this->_client;
    }

    /**
     * @return \TheLion\UseyourDrive\App
     */
    public function get_app()
    {
        return $this->get_processor()->get_app();
    }
}
