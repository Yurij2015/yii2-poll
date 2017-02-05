<?php

namespace davidjeddy\poll;

use \Yii;
use yii\base\Widget;

/**
 * Class PollWidget
 *
 * @author David J Eddy <me@davidjeddy.com>
 *
 * @package davidjeddy\poll
 */
class PollWidget extends Widget
{
    /**
     * @var array
     */
    public $answerOptions = [];

    /**
     * @var
     */
    public $answerOptionsData;

    /**
     * @var array
     */
    public $answers = [];

    /**
     * @var
     */
    public $isVote;

    /**
     * @var array
     */
    public $params = [
        'backgroundLinesColor' => '#D3D3D3',
        'linesColor'           => '#4F9BC7',
        'linesHeight'          => 15,
        'maxLineWidth'         => 300,
    ];

    /**
     * @var
     */
    public $pollData;

    /**
     * @var string
     */
    public $questionText = '';

    /**
     * @var int
     */
    public $sumOfVoices = 0;

    /**
     * @var array
     */

    // experimental ajax success override
    public $ajaxSuccess = [];

    /**
     * @param $name
     */
    public function setPollName($name)
    {

        $this->questionText = $name;
    }

    /**
     *
     */
    public function getDbData()
    {
        $data = Yii::$app->db->createCommand('SELECT * FROM poll_question WHERE question_text=:questionText')
            ->bindParam(':questionXText', $this->questionText)
            ->queryOne();

        $this->answerOptionsData = unserialize($data['answer_options']);
    }

    /**
     * @return int
     */
    public function saveNewPoll()
    {
        return \Yii::$app->db->createCommand()->insert('poll_question', [
            'answer_options' => $this->answerOptionsData,
            'question_text'      => $this->questionText,
        ])->execute();
    }

    /**
     * @param $params
     */
    public function setParams($params)
    {

        $this->params = array_merge($this->params, $params);
    }

    /**
     * @param $param
     *
     * @return mixed
     */
    public function getParams($param)
    {

        return $this->params[$param];
    }

    /**
     *
     */
    public function init()
    {
        parent::init();

        $pollDB = new PollDb;

        if ($pollDB->doTablesExist() < 3) {
            return false;
        }

        if ($this->answerOptions !== null) {
            $this->answerOptionsData = serialize($this->answerOptions);
        }

        // Check the DB for the poll, if not found treat the poll as a new poll and save it.
        if (!$pollDB->doesPollExist($this->questionText)) {
            $this->saveNewPoll();
        }

        // check that all Poll answers exist
        $pollDB->pollAnswerOptions($this);

        if (\Yii::$app->request->isAjax) {
            if (isset($_POST['VoicesOfPoll'])) {
                if ($_POST['question_text'] == $this->questionText && isset($_POST['VoicesOfPoll']['voice'])) {
                    $pollDB->updateAnswers(
                        $this->questionText,
                        $_POST['VoicesOfPoll']['voice'],
                        $this->answerOptions
                    );

                    $pollDB->updateUsers($this->questionText);
                }
            }
        }
        $this->getDbData();
        $this->answers = $pollDB->getVoicesData($this->questionText);

        $answerCount = count($this->answers);
        for ($i = 0; $i < $answerCount; $i++) {

            $this->sumOfVoices = $this->sumOfVoices + $this->answers[$i]['value'];
        }

        $this->isVote = $pollDB->isVote($this->questionText);
    }

    /**
     * @return string
     */
    public function run()
    {
        $model = new VoicesOfPoll;

        return $this->render('index', [
            'ajaxSuccess' => $this->ajaxSuccess,
            'answers'     => $this->answerOptions,
            'answersData' => $this->answers,
            'isVote'      => $this->isVote,
            'model'       => $model,
            'params'      => $this->params,
            'pollData'    => $this->pollData,
            'sumOfVoices' => $this->sumOfVoices,
        ]);
    }
}
