<?php

defined('BASEPATH') OR exit('O acesso direto a esse script não é permitido');

/**
 * Controlador Base. Possui funcoes úteis em diversas páginas do sistema.
 * 
 * @author Mateus Toledo <mateushtoledo@gmail.com>
 */
class Base_Controller extends CI_Controller {

    // Metodo construtor
    public function __construct() {
        parent::__construct();
    }

    /**
     * Obtém a data/horário atual no fuso de brasília.
     * 
     * @return string data/horário atual, no formato yyyy-mm-dd hh:mm:ss.
     */
    public function getCurrentDate() {
        // Definir timezone e horario
        $timezone = 'America/Sao_Paulo';
        $timestamp = time();

        // Criar timestamp com os parametros corretos
        $dt = new DateTime("now", new DateTimeZone($timezone));
        $dt->setTimestamp($timestamp);

        // Retornar data
        return $dt->format('Y-m-d H:i:s');
    }

    private function getTodayAsNumbers() {
        // Definir timezone e horario
        $timezone = 'America/Sao_Paulo';
        $timestamp = time();

        // Criar timestamp com os parametros corretos
        $dt = new DateTime("now", new DateTimeZone($timezone));
        $dt->setTimestamp($timestamp);

        // Retornar data
        return $dt->format('Y-m-d');
    }

    private function generateRandomString($length = 5) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    private function generateRandomFilename() {
        $filenamePreffix = $this->getTodayAsNumbers();
        $filenameSuffix = $this->generateRandomString();
        return "$filenamePreffix.$filenameSuffix.json";
    }

    protected function persistJson($swaggerData) {
        $filePath = $this->config->item("temp_file_storage");
        $filename = $this->generateRandomFilename();
        
        try {
            // Persist file
            $fp = fopen($filePath . $filename, 'w');
            fwrite($fp, json_encode($swaggerData));
            fclose($fp);
            // Return filename
            return $filename;
        } catch (Exception $ex) {
            var_dump($ex);
            die;
        }
    }
    
    protected function debugJson($data, $die = false) {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';

        if ($die) {
            die;
        }
    }
    
    protected function fileExists($filename) {
        $filePath = $this->config->item("temp_file_storage");
        return file_exists($filePath.$filename);
    }

    protected function getFilePath($filename) {
        return $this->config->item("temp_file_storage") . $filename;
    }
}
