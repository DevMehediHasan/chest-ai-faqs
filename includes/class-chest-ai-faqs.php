<?php

class ChestAIFaqs {

    private $admin;
    private $api;
    private $db;

    public function init() {
        $this->db = new ChestAIFaqs_DB();
        $this->admin = new ChestAIFaqs_Admin($this->db);
        $this->api = new ChestAIFaqs_API($this->db);
    }
}
