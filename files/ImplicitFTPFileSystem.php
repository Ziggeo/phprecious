<?php

require_once(dirname(__FILE__) . "/AbstractFileSystem.php");


Class ImplicitFTPFileSystem extends AbstractFileSystem {

    private $resource;

    protected function getClass() {
        return ImplicitFTPFile;
    }

    function __construct($options) {

        $ftp_server = 'ftps://' . $options["host"];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $ftp_server);
        curl_setopt($ch, CURLOPT_USERPWD, $options["username"] . ':' . $options["password"]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_FTP_SSL, CURLFTPSSL_TRY);
        curl_setopt($ch, CURLOPT_FTPSSLAUTH, CURLFTPAUTH_TLS);
        curl_setopt($ch, CURLOPT_PORT, $options["port"]);

        $this->resource = $ch;
    }

    function __destruct() {
        curl_close($this->resource);
        $this->resource = null;
    }

    public function ftp() {
        return $this->resource;
    }

}


Class ImplicitFTPFile extends AbstractFile {

    private function ftp() {
        return $this->file_system->ftp();
    }

    public function size() {
        return ftp_size($this->ftp(), $this->file_name);
    }

    public function exists() {
        return $this->size() >= 0;
    }

    public function delete() {
        if (!ftp_delete($this->ftp(), $this->file_name))
            throw new FileSystemException("Could not delete file");
    }

    private function get_ftp_mode($file) {
        $path_parts = pathinfo($file);
        if (!isset($path_parts['extension'])) return FTP_BINARY;
        switch (strtolower($path_parts['extension'])) {
            case 'am':case 'asp':case 'bat':case 'c':case 'cfm':case 'cgi':case 'conf':
            case 'cpp':case 'css':case 'dhtml':case 'diz':case 'h':case 'hpp':case 'htm':
            case 'html':case 'in':case 'inc':case 'js':case 'm4':case 'mak':case 'nfs':
            case 'nsi':case 'pas':case 'patch':case 'php':case 'php3':case 'php4':case 'php5':
            case 'phtml':case 'pl':case 'po':case 'py':case 'qmail':case 'sh':case 'shtml':
            case 'sql':case 'tcl':case 'tpl':case 'txt':case 'vbs':case 'xml':case 'xrc':
            return FTP_ASCII;
        }
        return FTP_BINARY;
    }

    public function readStream() {
        $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        stream_set_write_buffer($sockets[0], 0);
        stream_set_timeout($sockets[1], 0);
        $ret = ftp_nb_fget($this->ftp(), $sockets[0], $this->file_name, $this->get_ftp_mode($this->file_name));
        while ($ret == FTP_MOREDATA)
            $ret = ftp_nb_continue($this->ftp());
        return $sockets[1];
    }

    /*
    public function writeStream() {
        $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        stream_set_write_buffer($sockets[0], 0);
        stream_set_timeout($sockets[1], 0);
        ftp_nb_fput($this->ftp(), $this->file_name, $sockets[1], $this->get_ftp_mode($this->file_name));
        return $sockets[0];
    }
    */

    public function fromFile($file) {
        $file = $file->materialize();
        return $this->fromLocalFile($file->filename());
    }

    public function toLocalFile($file) {
        if (!ftp_get($this->ftp(), $file, $this->file_name, $this->get_ftp_mode($this->file_name)))
            throw new FileSystemException("Could not save to local file");
    }

    public function fromLocalFile($file) {
        $ch = $this->ftp();
        $fileStream = fopen($file, "r");
        $url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_setopt($ch, CURLOPT_URL, $url . "/" . $this->file_name);
        curl_setopt($ch, CURLOPT_UPLOAD, 1);
        curl_setopt($ch, CURLOPT_INFILE, $fileStream);

        $output = curl_exec($ch);
        $error_no = curl_errno($ch);
        if ($error_no != 0)
            throw new FileSystemException("Could not upload file to ftp." . " - Error No: " . $error_no );
    }

}
