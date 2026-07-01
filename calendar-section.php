<?php
include 'db/config.php';

// Get month/year from request (or default to current)
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$year  = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Fetch events for this month
$query = "SELECT * FROM event_calendar 
          WHERE MONTH(event_date) = $month 
            AND YEAR(event_date) = $year 
            AND status = 1
          ORDER BY event_date";
$result = mysqli_query($db, $query);

$events = [];
while ($row = mysqli_fetch_assoc($result)) {
    $day = date('j', strtotime($row['event_date']));
    $events[$day][] = $row;
}

// Calendar setup
$firstDay = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDay);
$dayOfWeek = date('w', $firstDay);
$monthName = date('F Y', $firstDay);

// Prev / Next month
$prevMonth = $month - 1; $prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }

$nextMonth = $month + 1; $nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }
?>

<div class="calendar-section">
  <div class="calendar-header">
    <h2><?php echo $monthName; ?></h2>
    <div class="nav-buttons">
      <a href="#" class="nav-btn" data-month="<?php echo $prevMonth; ?>" data-year="<?php echo $prevYear; ?>">← Previous</a>
      <a href="#" class="nav-btn" data-month="<?php echo date('m'); ?>" data-year="<?php echo date('Y'); ?>">Today</a>
      <a href="#" class="nav-btn" data-month="<?php echo $nextMonth; ?>" data-year="<?php echo $nextYear; ?>">Next →</a>
    </div>
  </div>

  <div class="calendar-grid">
    <?php foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?>
      <div class="day-header"><?php echo $d; ?></div>
    <?php endforeach; ?>

    <?php for ($i=0; $i<$dayOfWeek; $i++): ?>
      <div class="day-cell empty"></div>
    <?php endfor; ?>

    <?php for ($day=1; $day<=$daysInMonth; $day++): 
      $isToday = ($day == date('j') && $month == date('m') && $year == date('Y'));
      $dayEvents = $events[$day] ?? [];
      $hasEvents = !empty($dayEvents);
    ?>
      <div class="day-cell <?php echo $isToday ? 'today' : ''; ?> <?php echo $hasEvents ? 'has-events' : ''; ?>"
           <?php if ($hasEvents): ?>
             onclick='showEventModal(<?php echo json_encode($dayEvents); ?>, "<?php echo date("F d, Y", mktime(0,0,0,$month,$day,$year)); ?>")'
           <?php endif; ?>>
        <div class="day-number"><?php echo $day; ?></div>
        <?php if ($hasEvents): ?>
          <div class="event-preview"><?php echo count($dayEvents); ?> event<?php echo count($dayEvents) > 1 ? 's':''; ?></div>
        <?php endif; ?>
      </div>
    <?php endfor; ?>
  </div>
</div>
