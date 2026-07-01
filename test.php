<?php
// calendar_dashboard.php
session_start();
require_once('db/config.php');

// Get current month/year or from URL parameters
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Fetch events from database
$query = "SELECT * FROM event_calendar WHERE MONTH(event_date) = $month AND YEAR(event_date) = $year ORDER BY event_date";
$result = mysqli_query($db, $query);
$events = [];
while($row = mysqli_fetch_assoc($result)) {
    $day = date('j', strtotime($row['event_date']));
    $events[$day][] = $row;
}

// Calendar calculations
$firstDay = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDay);
$dayOfWeek = date('w', $firstDay);
$monthName = date('F Y', $firstDay);

// Navigation
$prevMonth = $month - 1;
$prevYear = $year;
if($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}
$nextMonth = $month + 1;
$nextYear = $year;
if($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Calendar Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }

        .dashboard {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .header h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
        }

        .main-content {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 20px;
        }

        .calendar-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .calendar-header h2 {
            color: #333;
            font-size: 24px;
        }

        .nav-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .nav-btn, .add-event-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .nav-btn:hover, .add-event-btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
        }

        .day-header {
            text-align: center;
            font-weight: 600;
            color: #667eea;
            padding: 15px;
            font-size: 14px;
        }

        .day-cell {
            min-height: 100px;
            border: 2px solid #f0f0f0;
            border-radius: 10px;
            padding: 10px;
            position: relative;
            cursor: pointer;
            transition: all 0.3s;
            background: #fafafa;
        }

        .day-cell:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }

        .day-cell.empty {
            background: transparent;
            border: none;
            cursor: default;
        }

        .day-cell.empty:hover {
            transform: none;
            box-shadow: none;
        }

        .day-cell.today {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
        }

        .day-cell.has-events {
            cursor: pointer;
        }

        .day-number {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .day-cell.today .day-number {
            color: #667eea;
            font-size: 18px;
        }

        .event-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin: 2px;
        }

        .event-dot.work { background: #3b82f6; }
        .event-dot.personal { background: #10b981; }
        .event-dot.important { background: #ef4444; }
        .event-dot.meeting { background: #f59e0b; }
        .event-dot.default { background: #6b7280; }

        .event-preview {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Event Tooltip Styles */
        .event-tooltip {
            display: none;
            position: fixed;
            z-index: 9999;
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 450px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            animation: tooltipFadeIn 0.3s ease;
        }

        .event-tooltip.show {
            display: block;
        }

        @keyframes tooltipFadeIn {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(10px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .tooltip-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .tooltip-header h3 {
            color: #333;
            font-size: 20px;
            margin: 0;
        }

        .tooltip-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
            transition: color 0.3s;
            padding: 0;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .tooltip-close:hover {
            color: #333;
            background: #f0f0f0;
        }

        .tooltip-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9998;
            animation: overlayFadeIn 0.3s ease;
        }

        .tooltip-overlay.show {
            display: block;
        }

        @keyframes overlayFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .event-details {
            margin-bottom: 20px;
            padding: 20px;
            background: #f8f9ff;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }

        .event-details:last-child {
            margin-bottom: 0;
        }

        .event-detail-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .event-detail-row {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            color: #666;
            font-size: 14px;
        }

        .event-detail-row strong {
            min-width: 100px;
            color: #333;
        }

        .event-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 10px;
        }

        .event-badge.expired {
            background: #fee2e2;
            color: #991b1b;
        }

        .event-badge.upcoming {
            background: #d1fae5;
            color: #065f46;
        }

        .event-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }

        .event-actions a {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.3s;
            text-align: center;
            flex: 1;
        }

        .edit-btn {
            background: #3b82f6;
            color: white;
        }

        .delete-btn {
            background: #ef4444;
            color: white;
        }

        .edit-btn:hover {
            background: #2563eb;
        }

        .delete-btn:hover {
            background: #dc2626;
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .sidebar-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .sidebar-card h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 18px;
        }

        .upcoming-event {
            padding: 15px;
            background: #f8f9ff;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
            transition: all 0.3s;
        }

        .upcoming-event:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .event-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .event-date {
            color: #666;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 5px;
        }

        .event-description {
            color: #888;
            font-size: 12px;
            margin-top: 5px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .event-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            margin-top: 5px;
        }

        .event-status.pending { background: #fef3c7; color: #92400e; }
        .event-status.confirmed { background: #d1fae5; color: #065f46; }
        .event-status.cancelled { background: #fee2e2; color: #991b1b; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            border-radius: 10px;
            color: white;
            text-align: center;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 13px;
            opacity: 0.9;
        }

        .no-events {
            color: #666;
            text-align: center;
            padding: 20px;
            font-style: italic;
        }

        @media (max-width: 1024px) {
            .main-content {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .calendar-grid {
                gap: 5px;
            }
            
            .day-cell {
                min-height: 70px;
                padding: 5px;
            }

            .calendar-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .nav-buttons {
                width: 100%;
            }

            .nav-btn, .add-event-btn {
                flex: 1;
                text-align: center;
            }

            .event-tooltip {
                padding: 20px;
                max-width: 95%;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="header">
            <h1>📅 Event Calendar Dashboard</h1>
            <p>Manage and view all your events in one place</p>
        </div>

        <div class="main-content">
            <div class="calendar-section">
                <div class="calendar-header">
                    <h2><?php echo $monthName; ?></h2>
                    <div class="nav-buttons">
                        <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="nav-btn">← Previous</a>
                        <a href="?month=<?php echo date('m'); ?>&year=<?php echo date('Y'); ?>" class="nav-btn">Today</a>
                        <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="nav-btn">Next →</a>
                        <a href="add-event.php" class="add-event-btn">+ Add Event</a>
                    </div>
                </div>

                <div class="calendar-grid">
                    <?php foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day): ?>
                        <div class="day-header"><?php echo $day; ?></div>
                    <?php endforeach; ?>

                    <?php for($i = 0; $i < $dayOfWeek; $i++): ?>
                        <div class="day-cell empty"></div>
                    <?php endfor; ?>

                    <?php for($day = 1; $day <= $daysInMonth; $day++): 
                        $isToday = ($day == date('j') && $month == date('m') && $year == date('Y'));
                        $dayEvents = isset($events[$day]) ? $events[$day] : [];
                        $hasEvents = !empty($dayEvents);
                    ?>
                        <div class="day-cell <?php echo $isToday ? 'today' : ''; ?> <?php echo $hasEvents ? 'has-events' : ''; ?>" 
                             <?php if($hasEvents): ?>
                             onclick='showEventModal(<?php echo json_encode($dayEvents); ?>, "<?php echo date("F d, Y", mktime(0, 0, 0, $month, $day, $year)); ?>")'
                             <?php endif; ?>>
                            <div class="day-number"><?php echo $day; ?></div>
                            <?php if($hasEvents): ?>
                                <div>
                                    <?php foreach($dayEvents as $event): 
                                        $eventType = isset($event['type']) ? strtolower($event['type']) : 'default';
                                    ?>
                                        <span class="event-dot <?php echo htmlspecialchars($eventType); ?>" 
                                              title="<?php echo htmlspecialchars($event['title']); ?>"></span>
                                    <?php endforeach; ?>
                                </div>
                                <div class="event-preview">
                                    <?php echo count($dayEvents); ?> event<?php echo count($dayEvents) > 1 ? 's' : ''; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="sidebar">
                <div class="sidebar-card">
                    <h3>📊 Statistics</h3>
                    <div class="stats-grid">
                        <?php
                        $totalEvents = mysqli_num_rows(mysqli_query($db, "SELECT * FROM event_calendar WHERE event_date >= CURDATE()"));
                        $todayEvents = mysqli_num_rows(mysqli_query($db, "SELECT * FROM event_calendar WHERE event_date = CURDATE()"));
                        ?>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $totalEvents; ?></div>
                            <div class="stat-label">Total Events</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $todayEvents; ?></div>
                            <div class="stat-label">Today</div>
                        </div>
                    </div>
                </div>

                <div class="sidebar-card">
                    <h3>🔔 Upcoming Events</h3>
                    <?php
                    $upcomingQuery = "SELECT * FROM event_calendar WHERE event_date >= CURDATE() ORDER BY event_date LIMIT 5";
                    $upcomingResult = mysqli_query($db, $upcomingQuery);
                    
                    if(mysqli_num_rows($upcomingResult) > 0):
                        while($event = mysqli_fetch_assoc($upcomingResult)):
                    ?>
                        <div class="upcoming-event">
                            <div class="event-title"><?php echo htmlspecialchars($event['title']); ?></div>
                            <div class="event-date">
                                🕐 <?php echo date('M d, Y', strtotime($event['event_date'])); ?>
                            </div>
                            <?php if(!empty($event['description'])): ?>
                                <div class="event-description"><?php echo htmlspecialchars($event['description']); ?></div>
                            <?php endif; ?>
                            <?php if(!empty($event['status'])): ?>
                                <span class="event-status <?php echo strtolower($event['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($event['status'])); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <p class="no-events">No upcoming events</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Event Tooltip Overlay -->
    <div id="tooltipOverlay" class="tooltip-overlay" onclick="closeEventTooltip()"></div>
    
    <!-- Event Tooltip -->
    <div id="eventTooltip" class="event-tooltip">
        <div class="tooltip-header">
            <h3 id="tooltipDate"></h3>
            <button class="tooltip-close" onclick="closeEventTooltip()">&times;</button>
        </div>
        <div id="tooltipBody"></div>
    </div>

    <script>
        function showEventModal(events, date) {
            const tooltip = document.getElementById('eventTooltip');
            const overlay = document.getElementById('tooltipOverlay');
            const tooltipDate = document.getElementById('tooltipDate');
            const tooltipBody = document.getElementById('tooltipBody');
            
            tooltipDate.textContent = date;
            
            let html = '';
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            events.forEach(event => {
                const eventDate = new Date(event.event_date);
                eventDate.setHours(0, 0, 0, 0);
                
                const isExpired = eventDate < today;
                const statusText = isExpired ? 'Expired' : 'Upcoming';
                const statusClass = isExpired ? 'expired' : 'upcoming';
                
                html += `
                    <div class="event-details">
                        <div class="event-detail-title">${escapeHtml(event.title)}</div>
                        ${event.description ? `<div class="event-detail-row"><strong>Description:</strong> ${escapeHtml(event.description)}</div>` : ''}
                        ${event.type ? `<div class="event-detail-row"><strong>Type:</strong> ${escapeHtml(event.type)}</div>` : ''}
                        ${event.location ? `<div class="event-detail-row"><strong>Location:</strong> ${escapeHtml(event.location)}</div>` : ''}
                        ${event.status ? `<div class="event-detail-row"><strong>Status:</strong> ${escapeHtml(event.status)}</div>` : ''}
                        <span class="event-badge ${statusClass}">${statusText}</span>
                       
                    </div>
                `;
            });
            
            tooltipBody.innerHTML = html;
            
            // Position tooltip in center
            overlay.classList.add('show');
            tooltip.classList.add('show');
            
            // Center the tooltip
            setTimeout(() => {
                const rect = tooltip.getBoundingClientRect();
                tooltip.style.left = `${(window.innerWidth - rect.width) / 2}px`;
                tooltip.style.top = `${Math.max(50, (window.innerHeight - rect.height) / 2)}px`;
            }, 10);
        }
        
        function closeEventTooltip() {
            const tooltip = document.getElementById('eventTooltip');
            const overlay = document.getElementById('tooltipOverlay');
            tooltip.classList.remove('show');
            overlay.classList.remove('show');
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Close tooltip with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeEventTooltip();
            }
        });
    </script>
</body>
</html>