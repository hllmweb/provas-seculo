<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Prova_model extends CI_Model {

    function __construct() {
        parent::__construct();
        $this->load->database();

        //$this->table = 'BD_SICA.ALUNO';
        // $this->view = 'BD_SICA.VW_AVAL_PROVA_INSCRITOS';
    }


    //lista de provas objetivas
    function lista($p) {

        //$this->db->select('P.*, I.*, T.DC_TIPO_PROVA, (BD_SICA.F_AVAL_PROVA_DISCIPLINAS(P.CD_PROVA)) AS DISCIPLINAS');
        $this->db->where('CD_ALUNO', $p['aluno']);
        $this->db->where('FL_FORMATO', 'O');
        //$this->db->where('P.PERIODO', $p['periodo']);
        //$this->db->where('DT_PROVA', date('d-M-Y'));
        $this->db->where('DT_PROVA', $p['data']);

        //$this->db->where('DT_PROVA', '20-MAR-2017');
        $this->db->order_by('CD_PROVA', 'ASC');
        //$this->db->join('BD_SICA.AVAL_PROVA_INSCRITOS I', 'P.CD_PROVA = I.CD_PROVA');
        //$this->db->join('BD_SICA.AVAL_TIPO_PROVA T', 'P.CD_TIPO_PROVA = T.CD_TIPO_PROVA');
        $query = $this->db->get('BD_SICA.VW_AVAL_PROVA_INSCRITOS')->result_object();

        //echo $this->db->last_query();
        return $query;
    }



    function lista_p($p){
      $query = $this->db->query("SELECT
      CASE 
       WHEN   TRUNC(DT_PROVA) = TRUNC(SYSDATE) AND TO_CHAR(SYSDATE,'HH24:MI') >=  HR_INICIO 
        THEN 'S'
        ELSE 'N' 
      END PODE_ABRIR 
      FROM BD_SICA.AVAL_PROVA P WHERE P.NUM_PROVA = '".$p['p_num_prova']."'");
    
      return $query->result_array();
    }

    //lista de provas discursivas
    function lista_discursiva($p){
        $this->db->where('CD_ALUNO', $p['aluno']);
        $this->db->where('FL_FORMATO', 'I');
        $this->db->where('DT_PROVA', date('d-M-Y'));
        $this->db->order_by('CD_PROVA', 'ASC');
        $query = $this->db->get('BD_SICA.VW_AVAL_PROVA_INSCRITOS')->result_object();
        return $query;
    }

    function exibeNota($p=null){

        return $this->db->query("SELECT FLG_EXIBE_RESULTADO FROM BD_SICA.VW_AVAL_PROVA_ALUNO_QUESTAO WHERE
                                 CD_PROVA_VERSAO = '".$p['prova']."' AND CD_ALUNO = '".$p['aluno']."' AND ROWNUM < 2");
    }

    function filtro($p) {
        $this->db->where('CD_PROVA', $p['codigo']);
        $query = $this->db->get('BD_SICA.AVAL_PROVA')->row();
        return $query;
    }

    function qtdeQuestoes($p) {
        $this->db->select('PQ.CD_PROVA');
        $this->db->where('P.DT_PROVA', date('d-M-Y'));
        $this->db->where('A.CD_ALUNO', $p['aluno']);
        $this->db->where('Q.FLG_TIPO', 'O');
        $this->db->order_by('PQD.POSICAO_INICIAL', 'ASC');
        $this->db->join('BD_SICA.AVAL_PROVA_INSCRITOS PI', 'PQ.CD_PROVA = PI.CD_PROVA');
        $this->db->join('BD_SICA.AVAL_PROVA P', 'PQ.CD_PROVA = P.CD_PROVA');
        $this->db->join('BD_SICA.ALUNO A', 'A.CD_ALUNO = PI.CD_ALUNO');
        $this->db->join('BD_ACADEMICO.AVAL_QUESTAO Q', 'PQ.CD_QUESTAO = Q.CD_QUESTAO');
        $this->db->join('BD_SICA.AVAL_PROVA_DISC PQD', 'PQD.CD_PROVA = PQ.CD_PROVA AND PQD.CD_DISCIPLINA = Q.CD_DISCIPLINA', 'left');
        $this->db->join('BD_SICA.CL_DISCIPLINA D', 'D.CD_DISCIPLINA = Q.CD_DISCIPLINA');
        //$this->db->group_by('PQ.CD_PROVA');

        $query = $this->db->get('BD_SICA.AVAL_PROVA_QUESTOES PQ')->result_array();

        echo $query;
    }

    function verificaAvalConcluida($p) {
        $this->db->select('CD_PROVA');
        $this->db->where('CD_PROVA', $p['prova']);
        $this->db->where('CD_ALUNO', $p['aluno']);

        $query = $this->db->get('BD_SICA.AVAL_PROVA_ALUNO_QUESTAO')->result_array();

        echo $query;
    }

    function tempo_discursiva($p){
        $this->db->where('CD_PROVA', $p['codigo']);
        $query = $this->db->get('BD_SICA.AVAL_PROVA')->row();
        return $query;
    }


    /**
     * Função que irá retornar o tempo da prova.
     * 
     * @param int $codigo
     * @return string
     */
    function tempo($codigo) {


        //obter o horário da prova
        $prova = $this->prova->filtro(array(
            'codigo' => $codigo
        ));

        $inicio = $prova->HR_INICIO;
        $fim = $prova->HR_FIM;

        $horaInicio = new DateTime($inicio);
        $horaFim = new DateTime($fim);
        $horaAtual = new DateTime();

        $tempo = 0;
        if ($horaAtual > $horaInicio) {
            $tempo = $horaFim->diff($horaAtual);
        } else {
            $tempo = $horaFim->diff($horaInicio);
        }

        //return ($tempo === null) ? "" : $tempo->format("%H:%i:%s");
    
        return $tempo->format("%H:%i:%s");
    }

    /**
     * Registra a presenca e o tempo da ultima acao em prova
     * 
     * @param array $params Vetor com a estrutura:
     * array(
     *      CD_PROVA => <int>,
     *      CD_ALUNO => <int>,
     *      HR_ULTIMA_ACAO => <string>
     * );
     */
    function registrarUltimaAcao($params) {
        $this->db->set("HR_ULTIMA_ACAO", $params['HR_ULTIMA_ACAO']);
        
        $this->db->where("CD_PROVA", $params['CD_PROVA']);
        $this->db->where("CD_ALUNO", $params['CD_ALUNO']);
        
        return $this->db->update("BD_SICA.AVAL_PROVA_INSCRITOS");
    }

    /**
     * Obtem o tempo da ultima acao realizada na prova.
     * 
     * @param array $params Vetor com a estrutura:
     * array(
     *      prova => <int>,
     *      aluno => <int>
     * );     
     */
    function getUltimaAcao($params) {
        $this->db->select("HR_ULTIMA_ACAO AS TEMPO");
        $this->db->from("BD_SICA.AVAL_PROVA_INSCRITOS");
        $this->db->where("CD_PROVA", $params['prova']);
        $this->db->where("CD_ALUNO", $params['aluno']);        
        
        $query = $this->db->get();
        return $query->row();
    }

    function getMinutoLiberaBtn($params){

        $this->db->select("MIN_LIBERACAO");
        $this->db->from("BD_SICA.AVAL_PROVA");
        $this->db->where("CD_PROVA", $params['CD_PROVA']);
        $query = $this->db->get();
        return $query->row();

    }


    //carregando prova discursiva
    function banco_prova($p) {
        $cursor = '';
        $params = array(
                  array('name' => ':P_OPERACAO',                'value' => $p['operacao']   ),
                  array('name' => ':P_CD_PROVA',                'value' => $p['prova']     ),
                  array('name' => ':P_NUM_PROVA',               'value' => $p['num_prova']  ),
                  array('name' => ':P_CHAMADA',                 'value' => $p['chamada']    ),
                  array('name' => ':P_PERIODO',                 'value' => $p['periodo']    ),
                  array('name' => ':P_CD_CURSO',                'value' => $p['curso']      ),

                  array('name' => ':P_DT_PROVA',                'value' => $p['data_prova'] ),

                  array('name' => ':P_QTDE_QUESTOES',           'value' => $p['avalQtdeObj']),
                  array('name' => ':P_VALOR_QUESTAO',           'value' => str_replace(',','.',$p['avalVlQuestaoObj'] )),
                  array('name' => ':P_FLG_PEND_PROCESSAMENTO',  'value' => $p['flg_pend']   ),
                  array('name' => ':P_FLG_WEB',                 'value' => $p['flg_web']    ),
                  array('name' => ':P_TITULO',                  'value' => $p['titulo']     ),

                  array('name' => ':P_CD_USUARIO',              'value' => $this->session->userdata('SGP_CODIGO') ),
                  array('name' => ':P_CD_ESTRUTURA',            'value' => $p['estrutura']  ),
                  array('name' => ':P_BIMESTRE',                'value' => $p['bimestre']   ),
                  array('name' => ':P_CD_TIPO_NOTA',            'value' => $p['tipo_nota']  ),
                  array('name' => ':P_NUM_NOTA',                'value' => $p['num_nota']   ),
                  array('name' => ':P_NOTA_MAXIMA',             'value' => str_replace(',','.',$p['avalTTPontoObj'])),

                  array('name' => ':P_CD_TIPO_PROVA',           'value' => $p['tipo_prova'] ),
                  array('name' => ':P_CD_PROFESSOR',            'value' => $p['professor']  ),
                  array('name' => ':P_CD_STATUS',               'value' => $p['status']     ),
                  array('name' => ':P_CD_PROVA_PAI',            'value' => $p['pai']        ),
                  array('name' => ':P_ORDEM_SERIE',             'value' => $p['serie']      ),
                  array('name' => ':P_CD_DISCIPLINA',           'value' => $p['disciplina'] ),

                  array('name' => ':P_QTDE_DISSERTATIVA',           'value' => $p['avalQtdeDis'] ),
                  array('name' => ':P_VALOR_QUESTAO_DISSERTATIVA',  'value' => str_replace(',','.',$p['avalVlQuestaoDis'] )),
                  array('name' => ':P_NOTA_DISSERTATIVA',           'value' => str_replace(',','.',$p['avalTTPontoDis'] )),

                  array('name' => ':P_HR_INICIO',               'value' => $p['hora_inicio'] ),
                  array('name' => ':P_HR_FIM',                  'value' => $p['hora_fim'] ),
                  array('name' => ':P_FL_FORMATO',              'value' => $p['avalFormato'] ),


                  array('name' => ':v_RETORNO',                 'value' => 0,         'type' => OCI_B_ROWID),
                  array('name' => ':P_CURSOR',                  'value' => $cursor,   'type' => OCI_B_CURSOR)
        );

       return $this->db->procedure('BD_ACADEMICO','AVAL_MANTER_PROVA',$params);
    }

    //carregando cabeçalho
    function cabecalho($p) {

        $cursor = '';
        $params = array(
            array('name' => ':p_CD_PROVA', 'value' => $p['prova']),
            array('name' => ':p_CD_ALUNO', 'value' => $p['aluno']),
            array('name' => ':RC1', 'value' => $cursor, 'type' => OCI_B_CURSOR)
        );
        $res = $this->db->procedure('BD_SICA', 'SP_AVAL_PROVA_CABECALHO', $params);
        //print_r($params);exit;
        //print_r($res);exit;
        if(count($res) == 0){
           return FALSE;
        }else{
           return($res);
        }
    }

    //carregando questão
    function prova_questao($p) {
        $cursor = '';
        $params = array(
            array('name' => ':P_OPERACAO',     'value' => $p['operacao']),
            array('name' => ':P_CD_PROVA',     'value' => $p['prova']),
            array('name' => ':P_CD_QUESTAO',   'value' => $p['questao']),
            array('name' => ':P_POSICAO',      'value' => $p['posicao']),
            array('name' => ':P_VALOR',        'value' => $p['valor']),
            array('name' => ':P_CD_DISCIPLINA','value' => $p['disciplina']),
            array('name' => ':P_FLG_ANULADA',  'value' => $p['anulada']),
            array('name' => ':P_CURSOR',       'value' => $cursor, 'type' => OCI_B_CURSOR)
        );
        return $this->db->procedure('BD_ACADEMICO','AVAL_MANTER_PROVA_QUESTAO',$params);
    }


    //select prova discursiva
    function select_discursiva($p){
      $this->db->select("FEZ_PROVA");
      $this->db->where('CD_PROVA', $p['prova']);
      $this->db->where('CD_ALUNO', $p['aluno']);

      $query = $this->db->get('BD_SICA.AVAL_PROVA_INSCRITOS')->result_array();

      return $query;
    }

    //finaliza prova discursiva
    function finaliza_discursiva($p){
      $this->db->set("FEZ_PROVA", 1);
      $this->db->where('CD_PROVA', $p['prova']);
      $this->db->where('CD_ALUNO', $p['aluno']);
      return $this->db->update("BD_SICA.AVAL_PROVA_INSCRITOS");
    }





    //lista discursova do aluno relacionado a prova
    // function select_discursiva(){
      
    // }

}
