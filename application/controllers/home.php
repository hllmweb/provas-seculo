<?php
//
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Home extends CI_Controller {
    
     function __construct() {
        parent::__construct();

        $this->load->model('prova_model', 'prova', TRUE);
        $this->load->model('aluno_model', 'aluno', TRUE);  


        $this->load->helper(array('form', 'url', 'html', 'directory'));
        $this->load->library(array('session','bloqueio_lib','form_validation'));
    }
    
    function index() {     

        $this->form_validation->run();
        
        // LISTA AS PROVAS QUE O ALUNO TEM NO DIA
        $param = array(
           'aluno' => $this->session->userdata('PVO_USER'),
           'data'   => date('d/m/Y')
           //'data' =>  "DT_PROVA = to_date('".date('d/m/Y')."', 'DD/MM/YYYY')"
           //  'data' => "DT_PROVA = '17/11/2021'"
	   );
	

        $listar = $this->prova->lista($param);


        // $param_1 = array(
        //     'p_num_prova' => $lista->NUM_PROVA
        // );

        
        #$listar_p = $this->prova->lista_p($param_1);

        $data = array(
            'listar'             => $listar,
            #'listar_p'           => $listar_p,
            //'listar_discursiva'  => $this->prova->lista_discursiva($param)
        );
        
        $this->load->view('home/index', $data);
    }

    //verifica se o aluno já acessou a prova
    function verifica_acesso(){
 
        $prova = $this->input->post('cesProva');
        $this->session->set_userdata('CES_PROVA', $prova);
        $this->session->set_userdata('CES_MATERIA', $this->input->post('materia'));
        $serie = $this->input->post("serie");
        $this->session->set_userdata('SERIE',$serie);

      
            $bloqueio          = new Bloqueio_lib();
            $bloqueio->aluno   =  $this->session->userdata('PVO_USER');
            $bloqueio->ip      =  $_SERVER['REMOTE_ADDR'];
            $bloqueio->prova   =  $prova;
            $bloqueio->maquina =  gethostbyaddr($_SERVER['REMOTE_ADDR']);

            $rs = $bloqueio->verificacao();

            //se ip no banco for o msm que o atual ele vai para prova, senao para o bloqueio
            if($rs['status']){
            //if(true){  

                  $this->session->set_userdata('nreload',1);// NAO DEIXA O ALUNO DAR RELOAD NA PAGINA DA PROVA
                  redirect('prova/index');
            }else{

                $data = array(
                'rs' => $rs,
                );

                $this->load->view('home/bloqueio', $data);                   
            }
    }

    function liberar(){

        //
        $param = array(
            array('campo' => "USU_LOGIN = upper('".$this->input->post('usuario')."')",  'valor' => ''),
            array('campo' => 'USU_SENHA',  'valor' => $this->input->post('senha')),
            array('campo' => 'TIPO_TEXTO', 'valor' => 'FUNCIONARIO'),
        );

         $rest = $this->aluno->pesquisar_id($param);
        
            if (!$rest) {
                echo "Erro ao fazer essa liberação!";
            } else {

            $bloqueio = new Bloqueio_lib();
            $bloqueio->aluno =  $this->session->userdata('PVO_USER');
            $bloqueio->prova  =  $this->session->userdata('CES_PROVA');
            $bloqueio->ip      =  $_SERVER['REMOTE_ADDR'];
            $bloqueio->maquina =  gethostbyaddr($_SERVER['REMOTE_ADDR']);

            $rs = $bloqueio->liberacao();

            if($rs){
                $this->session->set_userdata('nreload',1);//NAO DEIxA O ALUNO DAR RELOAD NA PAGINA DA PROVA
                echo 'true';
            }else{
                echo 'Erro ao libera Máquina !';
            }
        }

    }

}