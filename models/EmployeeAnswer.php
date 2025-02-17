<?php

/**
 * Class EmployeeAnswer
 *
 * Handles operations related to employee assessments, including creating, updating,
 * and retrieving assessment details, feedback, and ratings.
 */
class EmployeeAnswer
{
    private $conn;
    private $table_name = "assessments";
    private $details_table = "assessment_details";
    private $feedback_table = "feedback";

    public $assessment_id;
    public $user_id;
    public $question_id;
    public $criterion;
    public $self_rating;
    public $rating;
    public $period;
    
    public $baseYear;
    public $baseMonth;
    

    /**
     * EmployeeAnswer constructor.
     *
     * @param PDO $db A PDO database connection.
     */
    public function __construct($db)
    {
        $this->conn = $db;
        $this->baseMonth = 1;
        $this->baseYear = 2025;
    }

    /**
     * Create a new assessment.
     *
     * Inserts a new assessment record into the database.
     *
     * @return bool True on success, False on failure.
     */
    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . " SET user_id=:user_id, question_id=:question_id, rating=:rating, period=:period";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':question_id', $this->question_id);
        $stmt->bindParam(':rating', $this->rating);
        $stmt->bindParam(':period', $this->period);

        return $stmt->execute();
    }

    /**
     * Create a new assessment detail entry.
     *
     * Inserts a new detail record into the assessment_details table.
     *
     * @return bool True on success, False on failure.
     */
    public function createDetail($assessment_id, $criterion, $self_rating)
    {
        $query = "INSERT INTO " . $this->details_table . " (assessment_id, criterion, self_rating) 
                  VALUES (:assessment_id, :criterion, :self_rating)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':assessment_id', $assessment_id);
        $stmt->bindParam(':criterion', $criterion);
        $stmt->bindParam(':self_rating', $self_rating);

        return $stmt->execute();
    }

    /**
     * Retrieve assessment details for a specific user and period.
     *
     * @param int $user_id The ID of the user.
     * @param string $period The period in 'Y-m-d' format.
     * @return PDOStatement The PDO statement object containing the result set.
     */
    public function readByUserAndPeriod($user_id, $period)
    {
        $query = "SELECT q.question_text, a.rating, a.question_id FROM " . $this->table_name . " a
                  JOIN questions q ON a.question_id = q.question_id
                  WHERE a.user_id = ? AND a.period = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->bindParam(2, $period);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Create or update an assessment record.
     *
     * @param int $user_id The ID of the user.
     * @param int $total_score The total score for the assessment.
     * @param string $period The period in 'Y-m-d' format.
     * @return bool True on success, False on failure.
     */
    public function createAssessment($user_id, $self_score, $month, $year, $note)
    {
        $query = "INSERT INTO assessments (user_id, self_score, month, year, self_note) 
                  VALUES (:user_id, :self_score, :month, :year, :self_note)";
        
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':self_score', $self_score);
        $stmt->bindParam(':month', $month);
        $stmt->bindParam(':year', $year);
        $stmt->bindParam(':self_note', $note);
        if ($stmt->execute())
            return $this->conn->lastInsertId();
        
        return true;
    }

    /**
     * Update the supervisor's score for an assessment.
     *
     * @param int $user_id The ID of the user.
     * @param string $period The period in 'Y-m-d' format.
     * @param int $supervisor_score The supervisor's total score.
     * @return bool True on success, False on failure.
     */
    public function updateSupervisorScore($assessment_id)
    {
        $supervisor_score = 0;
        $supervisor_note = $_POST['supervisor_note']; // Capture the feedback
    
        foreach ($_POST['ratings'] as $detail_id => $rating) {
            $this->updateSupervisorRating($detail_id, $rating);
            $supervisor_score += $rating;
        }
        $supervisor_score = round($supervisor_score / count($_POST['ratings']),2);
        $query = "UPDATE assessments SET supervisor_score = :supervisor_score, supervisor_note = :supervisor_note WHERE assessment_id = :assessment_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':supervisor_score', $supervisor_score);
        $stmt->bindParam(':supervisor_note', $supervisor_note);
        $stmt->bindParam(':assessment_id', $assessment_id);

        return $stmt->execute();
    }
    
    public function updateSupervisorRating($detail_id, $rating)
    {
        $query = "UPDATE assessment_details SET supervisor_rating = :rating WHERE detail_id = :detail_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':rating', $rating);
        $stmt->bindParam(':detail_id', $detail_id);
        return $stmt->execute();
    }
    /**
     * Update a specific question's rating for an assessment.
     *
     * @param int $user_id The ID of the user.
     * @param int $question_id The ID of the question.
     * @param int $rating The rating to be updated.
     * @param string $period The period in 'Y-m-d' format.
     * @return void
     */
    public function updateRating($user_id, $question_id, $rating, $period)
    {
        $query = "UPDATE " . $this->details_table . " 
                  SET supervisor_rating = :rating 
                  WHERE assessment_id = (
                      SELECT assessment_id FROM assessments 
                      WHERE user_id = :user_id AND period = :period
                  ) AND criterion = :question_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':rating', $rating);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':question_id', $question_id);
        $stmt->bindParam(':period', $period);
        $stmt->execute();
    }

    /**
     * Retrieve the self-rating for a specific question and period.
     *
     * @param int $user_id The ID of the user.
     * @param int $question_id The ID of the question.
     * @param string $period The period in 'Y-m-d' format.
     * @return mixed The self-rating if found, or False otherwise.
     */
    public function getRating($user_id, $question_id, $period)
    {
        $query = "SELECT self_rating FROM " . $this->details_table . " 
                  WHERE assessment_id = (
                      SELECT assessment_id FROM assessments 
                      WHERE user_id = :user_id AND period = :period
                  ) AND criterion = :question_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':question_id', $question_id);
        $stmt->bindParam(':period', $period);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    /**
     * Retrieve all employees' assessments for a specific period.
     *
     * @param string $period The period in 'Y-m-d' format.
     * @return PDOStatement The PDO statement object containing the result set.
     */
    public function getAssessmentByPeriod($month, $year)
    {
        $where = '';
        if($_SESSION['role'] == 'Supervisor')
            $where = ' AND u.supervisor_id = :supervisor_id';
        $query = "SELECT u.name, u.designation, u.supervisor_id, a.*
                  FROM assessments a
                  JOIN users u ON a.user_id = u.user_id
                  WHERE a.month = :month AND a.year = :year
                  $where
                  ORDER BY u.designation ASC
                  ";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':month', $month);
        $stmt->bindParam(':year', $year);
        if($where != '')
            $stmt->bindParam(':supervisor_id', $_SESSION['user_id']);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    function getAssessment($assessment_id)
    {
         // Fetch the assessment details
        $query = "SELECT a.*, b.name as user, b.supervisor_id FROM " . $this->table_name . " AS a
        LEFT JOIN users AS b ON a.user_id = b.user_id
                  WHERE assessment_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $assessment_id);
        $stmt->execute();

        $assessment = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch the assessment as an associative array

        // Check if the assessment exists
        if (!$assessment) {
            return null; // Return null if no record is found
        }

        // Fetch the related assessment_details records
        $details_query = "SELECT a.*, b.question_text as question FROM assessment_details AS a 
        LEFT JOIN questions AS b ON a.criterion = b.question_id
        WHERE assessment_id = :id";
        $details_stmt = $this->conn->prepare($details_query);
        $details_stmt->bindParam(':id', $assessment_id);
        $details_stmt->execute();

        $details = $details_stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all related records as an array

        // Add the details array to the assessment array under the key 'questions'
        $assessment['questions'] = $details;

        return $assessment; // Return the combined result
    }
    
    function getAssessmentByID($assessment_id)
    {
         // Fetch the assessment details
        $query = "SELECT a.*, b.name as user FROM " . $this->table_name . " AS a
        LEFT JOIN users AS b ON a.user_id = b.user_id
                  WHERE assessment_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $assessment_id);
        $stmt->execute();

        $assessment = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch the assessment as an associative array

        // Check if the assessment exists
        if (!$assessment) {
            return null; // Return null if no record is found
        }

        // Fetch the related assessment_details records
        $details_query = "SELECT a.*, b.question_text as question FROM assessment_details AS a 
        LEFT JOIN questions AS b ON a.criterion = b.question_id
        WHERE assessment_id = :id";
        $details_stmt = $this->conn->prepare($details_query);
        $details_stmt->bindParam(':id', $assessment_id);
        $details_stmt->execute();

        $details = $details_stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all related records as an array

        // Add the details array to the assessment array under the key 'questions'
        $assessment['questions'] = $details;

        return $assessment; // Return the combined result

    }
    /**
     * Update the total score for an assessment.
     *
     * @param int $assessment_id The ID of the assessment.
     * @param int $total_score The total score to be updated.
     * @return void
     */
    public function updateTotalScore($assessment_id, $total_score)
    {
        $query = "UPDATE " . $this->table_name . " 
                  SET total_score = :total_score 
                  WHERE assessment_id = :assessment_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':total_score', $total_score);
        $stmt->bindParam(':assessment_id', $assessment_id);
        $stmt->execute();
    }

    /**
     * Get the assessment ID for a specific user and period.
     *
     * @param int $user_id The ID of the user.
     * @param string $period The period in 'Y-m-d' format.
     * @return int|false The assessment ID if found, or False otherwise.
     */
    public function getAssessmentId($user_id, $period)
    {
        $query = "SELECT assessment_id FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND period = :period";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':period', $period);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    /**
     * Update general feedback for an assessment.
     *
     * @param int $user_id The ID of the user.
     * @param string $period The period in 'Y-m-d' format.
     * @param string $feedback The feedback comments.
     * @param string $role The role of the person providing feedback.
     * @return void
     */
    public function updateGeneralFeedback($user_id, $period, $feedback, $role)
    {
        // Check if general feedback already exists
        $query = "SELECT feedback_id FROM " . $this->feedback_table . " 
                  WHERE assessment_id = (
                      SELECT assessment_id FROM assessments 
                      WHERE user_id = :user_id AND period = :period
                  ) AND role = :role AND criterion IS NULL";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':period', $period);
        $stmt->bindParam(':role', $role);
        $stmt->execute();
        $feedback_id = $stmt->fetchColumn();

        if ($feedback_id) {
            // Update existing general feedback
            $query = "UPDATE " . $this->feedback_table . " 
                      SET comments = :feedback 
                      WHERE feedback_id = :feedback_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':feedback', $feedback);
            $stmt->bindParam(':feedback_id', $feedback_id);
        } else {
            // Insert new general feedback
            $query = "INSERT INTO " . $this->feedback_table . " (assessment_id, user_id, comments, role) 
                      VALUES (
                          (SELECT assessment_id FROM assessments WHERE user_id = :user_id AND period = :period), 
                          :user_id, 
                          :feedback, 
                          :role
                      )";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':feedback', $feedback);
            $stmt->bindParam(':period', $period);
            $stmt->bindParam(':role', $role);
        }
        $stmt->execute();
    }

    /**
     * Get general feedback for an assessment.
     *
     * @param int $user_id The ID of the user.
     * @param string $period The period in 'Y-m-d' format.
     * @param string $role The role of the person providing feedback.
     * @return string|false The feedback comments if found, or False otherwise.
     */
    public function getGeneralFeedback($user_id, $period, $role)
    {
        $query = "SELECT comments FROM " . $this->feedback_table . " 
                  WHERE assessment_id = (
                      SELECT assessment_id FROM assessments 
                      WHERE user_id = :user_id AND period = :period
                  ) AND role = :role AND criterion IS NULL";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':period', $period);
        $stmt->bindParam(':role', $role);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    /**
     * Check if an assessment exists for a specific user and period.
     *
     * @param int $user_id The ID of the user.
     * @param string $period The period in 'Y-m-d' format.
     * @return bool True if the assessment exists, False otherwise.
     */
    public function checkAssessmentExists($user_id, $month, $year)
    {
        $query = "SELECT COUNT(*) FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND month = :month AND year = :year";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':month', $month);
        $stmt->bindParam(':year', $year);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Get all self-ratings for a user in a specific period.
     *
     * @param int $user_id The ID of the user.
     * @param string $period The period in 'Y-m-d' format.
     * @return array An associative array of criterion IDs and their corresponding self-ratings.
     */
    public function getRatingsForUser($user_id, $period)
    {
        $query = "SELECT criterion, self_rating, supervisor_rating, hr_rating 
              FROM " . $this->details_table . " and
              JOIN assessments a ON ad.assessment_id = a.assessment_id
              WHERE a.user_id = :user_id AND a.period = :period";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':period', $period);
        $stmt->execute();

        // Fetch the ratings and return them as an associative array
        $ratings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $ratings[$row['criterion']] = [
                'self_rating' => $row['self_rating'],
                'supervisor_rating' => $row['supervisor_rating'],
                'hr_rating' => $row['hr_rating']
            ];
        }

        return $ratings;
    }


    /**
     * Retrieve all assessments for a specific user.
     *
     * @param int $user_id The ID of the user.
     * @return PDOStatement The PDO statement object containing the result set.
     */
    public function readByUser($user_id)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id ORDER BY year DESC,month DESC LIMIT 12";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getCompletedPeriods($user_id)
    {
        $query = "SELECT DISTINCT period FROM " . $this->table_name . " WHERE user_id = :user_id ORDER BY period DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateHrRating($user_id, $question_id, $rating, $period)
    {
        $query = "UPDATE " . $this->details_table . " 
              SET hr_rating = :rating 
              WHERE assessment_id = (
                  SELECT assessment_id FROM assessments 
                  WHERE user_id = :user_id AND period = :period
              ) AND criterion = :question_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':rating', $rating);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':question_id', $question_id);
        $stmt->bindParam(':period', $period);
        $stmt->execute();
    }
    public function updateHrScore($user_id, $period, $hr_score)
    {
        $query = "UPDATE assessments 
              SET hr_score = :hr_score 
              WHERE user_id = :user_id AND period = :period";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':hr_score', $hr_score);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':period', $period);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
    public function updateGeneralFeedbackHR($user_id, $period, $feedback, $role)
    {
        // Check if general feedback already exists
        $query = "SELECT feedback_id FROM " . $this->feedback_table . " 
              WHERE assessment_id = (
                  SELECT assessment_id FROM assessments 
                  WHERE user_id = :user_id AND period = :period
              ) AND role = :role AND criterion IS NULL";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':period', $period);
        $stmt->bindParam(':role', $role);
        $stmt->execute();
        $feedback_id = $stmt->fetchColumn();

        if ($feedback_id) {
            // Update existing general feedback
            $query = "UPDATE " . $this->feedback_table . " 
                  SET comments = :feedback 
                  WHERE feedback_id = :feedback_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':feedback', $feedback);
            $stmt->bindParam(':feedback_id', $feedback_id);
        } else {
            // Insert new general feedback
            $query = "INSERT INTO " . $this->feedback_table . " (assessment_id, user_id, comments, role) 
                  VALUES (
                      (SELECT assessment_id FROM assessments WHERE user_id = :user_id AND period = :period), 
                      :user_id, 
                      :feedback, 
                      :role
                  )";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':feedback', $feedback);
            $stmt->bindParam(':period', $period);
            $stmt->bindParam(':role', $role);
        }
        $stmt->execute();
    }
    public function calculateHrScore($user_id, $period, $criteria)
    {
        $hr_score = 0;
        foreach ($criteria as $criterion => $rating) {
            // Assuming each criterion rating is between 1 and 5
            $hr_score += $rating;

            // Update or insert HR rating for each criterion
            $query = "UPDATE " . $this->details_table . " 
                  SET hr_rating = :hr_rating 
                  WHERE assessment_id = (
                      SELECT assessment_id FROM assessments 
                      WHERE user_id = :user_id AND period = :period
                  ) AND criterion = :criterion";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':hr_rating', $rating);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':criterion', $criterion);
            $stmt->bindParam(':period', $period);
            $stmt->execute();
        }

        // Update total HR score in assessments table
        $this->updateHrScore($user_id, $period, $hr_score);
        return $hr_score;
    }
    public function getAllPeriods()
    {
        $query = "SELECT DISTINCT DATE_FORMAT(period, '%Y-%m-01') AS period FROM " . $this->table_name . " ORDER BY period DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
    public function getSupervisorRatingsForUser($user_id, $period)
    {
        $query = "SELECT criterion, supervisor_rating 
              FROM " . $this->details_table . " ad
              JOIN assessments a ON ad.assessment_id = a.assessment_id
              WHERE a.user_id = :user_id AND a.period = :period";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':period', $period);
        $stmt->execute();

        $ratings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $ratings[$row['criterion']] = $row['supervisor_rating'];
        }

        return $ratings;
    }
    
    
    public function getHrScoreForUser($user_id, $period)
    {
        $query = "SELECT hr_score FROM assessments WHERE user_id = :user_id AND period = :period";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':period', $period);
        $stmt->execute();

        return $stmt->fetchColumn();
    }

    public function getRatingsForEmployee($user_id, $period)
    {
        $query = "SELECT criterion, self_rating 
              FROM " . $this->details_table . " ad
              JOIN assessments a ON ad.assessment_id = a.assessment_id
              WHERE a.user_id = :user_id AND a.period = :period";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':period', $period);
        $stmt->execute();

        $ratings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $ratings[$row['criterion']] = $row['self_rating'];
        }

        return $ratings;
    }
    
    function getHrQuestions()
    {
        $sql = "SELECT * FROM hr_question ORDER BY id_hr_question ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    function getHrReview($id)
    {
        $sql = "SELECT a.*, b.question FROM hr_review AS a
        LEFT JOIN hr_question AS b ON a.id_hr_question = b.id_hr_question
        WHERE a.assessment_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        while($row = $stmt->fetch(PDO::FETCH_ASSOC))
        {
            $result[$row['id_hr_question']] = $row;
        }
        
        return $result;
        
    }
    
    function setHrReview($id)
    {
        $question = $_POST['question'];
        $feedback = $_POST['feedback'];
        $score = 0 ;
        $count = count($question);
        $values = array();
        foreach ($question as $id_question=>$rating)
        {
            $score += intval($rating);
            $values[] = '('.$id.','.$id_question.','.$rating.')';
        }
        $val = implode(',',$values);
        $sql = "INSERT INTO hr_review (assessment_id, id_hr_question, rating) VALUES ".$val;
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        $hr_rating = $score/$count;
        
        $sql2 = "UPDATE assessments SET hr_score = :score, hr_note = :note WHERE assessment_id = :id";
        $stmt2 = $this->conn->prepare($sql2);
        $stmt2->bindParam(':score', $hr_rating);
        $stmt2->bindParam(':note', $feedback);
        $stmt2->bindParam(':id', $id);
        
        if($stmt2->execute())
            return true;
        
        return false;
    }
    
    function updateHrReview($id)
    {
        $question = $_POST['question'];
        $feedback = $_POST['feedback'];
        $score = 0 ;
        $count = count($question);
        $values = array();
        foreach ($question as $id_question=>$rating)
        {
            $score += intval($rating);
            $sql = "UPDATE hr_review SET rating = :rating WHERE assessment_id = :id && id_hr_question = :id_question";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':rating', $rating);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':id_question', $id_question);
            $stmt->execute();
        }
        
        $hr_rating = $score/$count;
        
        $sql2 = "UPDATE assessments SET hr_score = :score, hr_note = :note WHERE assessment_id = :id";
        $stmt2 = $this->conn->prepare($sql2);
        $stmt2->bindParam(':score', $hr_rating);
        $stmt2->bindParam(':note', $feedback);
        $stmt2->bindParam(':id', $id);
        
        if($stmt2->execute())
            return true;
        
        return false;
    }
    function getFillAssessment($user_id)
    {

        // Current date
        $currentYear = date('Y');
        $currentMonth = date('n'); // 'n' for numeric month without leading zero
        

        // Generate all months from base date to current date
        $allMonths = [];
        $year = $this->baseYear;
        $month = $this->baseMonth;

        while ($year < $currentYear || ($year == $currentYear && $month <= $currentMonth)) {
            $allMonths[] = sprintf('%04d-%02d', $year, $month); // Format as YYYY-MM
            $month++;
            if ($month > 12) {
                $month = 1;
                $year++;
            }
        }

        // Fetch filled months from the database
        $query = "SELECT CONCAT(year, '-', LPAD(month, 2, '0')) FROM assessments WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $filledMonths = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Find missing months
        $missingMonths = array_diff($allMonths, $filledMonths);
        $missingDates = [];
        foreach ($missingMonths as $missingMonth) {
            list($year, $month) = explode('-', $missingMonth); // Split YYYY-MM into components
            $missingDates[] = [
                'year' => (int)$year,
                'month' => (int)$month,
            ];
        }

        return $missingDates;
    }
    
    function checkSupervisor($user_id)
    {
        $supervisor_id = $_SESSION['user_id'];
        $sql = "SELECT COUNT(*) FROM users WHERE user_id = :user_id AND supervisor_id = :supervisor_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':supervisor_id', $supervisor_id);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }
}
