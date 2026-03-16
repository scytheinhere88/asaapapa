<?php

class AutopilotRenderer
{
    public static function renderHeader()
    {
        return <<<HTML
<div class="ap-header">
  <div class="ap-title">🤖 AI Autopilot</div>
  <div class="ap-subtitle">AUTOMATED BULK CONTENT ANALYSIS</div>
</div>
HTML;
    }

    public static function renderJobCard($job, $progress = null)
    {
        $statusColors = [
            'pending' => '#fbbf24',
            'processing' => '#3b82f6',
            'completed' => '#22c55e',
            'failed' => '#ef4444'
        ];

        $status = $job['status'];
        $color = $statusColors[$status] ?? '#6b7280';
        $createdAt = date('M j, Y g:i A', strtotime($job['created_at']));

        $progressHtml = '';
        if ($progress) {
            $percentage = $progress['total'] > 0 ? round(($progress['completed'] / $progress['total']) * 100) : 0;
            $progressHtml = <<<HTML
<div style="margin-top:12px;">
  <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:11px;color:var(--muted);">
    <span>{$progress['completed']} / {$progress['total']} domains</span>
    <span>{$percentage}%</span>
  </div>
  <div style="height:6px;background:#1a1a1a;border-radius:3px;overflow:hidden;">
    <div style="height:100%;width:{$percentage}%;background:{$color};transition:width 0.3s;"></div>
  </div>
</div>
HTML;
        }

        return <<<HTML
<div class="job-card" data-job-id="{$job['id']}">
  <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:12px;">
    <div>
      <div style="font-weight:700;color:#fff;margin-bottom:4px;">Job #{$job['id']}</div>
      <div style="font-size:11px;color:var(--muted);">{$createdAt}</div>
    </div>
    <span class="status-badge" style="background:{$color};color:#fff;padding:4px 12px;border-radius:12px;font-size:10px;font-weight:700;text-transform:uppercase;">{$status}</span>
  </div>
  {$progressHtml}
  <div style="margin-top:12px;display:flex;gap:8px;">
    <button onclick="viewJobResults('{$job['id']}')" class="btn btn-secondary btn-sm">View Results</button>
    <button onclick="deleteJob('{$job['id']}')" class="btn btn-danger btn-sm">Delete</button>
  </div>
</div>
HTML;
    }

    public static function renderPipeline($currentStep = 1)
    {
        $steps = [
            ['icon' => '📝', 'label' => 'Input'],
            ['icon' => '🔍', 'label' => 'Detect'],
            ['icon' => '🤖', 'label' => 'Analyze'],
            ['icon' => '✅', 'label' => 'Results']
        ];

        $html = '<div class="ap-pipeline">';
        foreach ($steps as $index => $step) {
            $stepNum = $index + 1;
            $active = $stepNum <= $currentStep ? 'active' : '';
            $html .= <<<HTML
<div class="ap-step {$active}">
  <div class="ap-step-icon">{$step['icon']}</div>
  <div class="ap-step-label">{$step['label']}</div>
</div>
HTML;
            if ($stepNum < count($steps)) {
                $html .= '<div class="ap-step-connector"></div>';
            }
        }
        $html .= '</div>';

        return $html;
    }

    public static function renderEmptyState()
    {
        return <<<HTML
<div style="text-align:center;padding:60px 20px;">
  <div style="font-size:64px;margin-bottom:16px;">🤖</div>
  <h3 style="color:#fff;margin:0 0 8px;">No Autopilot Jobs Yet</h3>
  <p style="color:var(--muted);margin:0;">Create your first job to get started with automated content analysis.</p>
  <button onclick="showCreateJobModal()" class="btn btn-amber" style="margin-top:24px;">Create First Job</button>
</div>
HTML;
    }

    public static function renderStyles()
    {
        return <<<CSS
<style>
:root{
  --ap-gold:#f0a500;
  --ap-purple:#c084fc;
  --ap-teal:#00d4aa;
  --ap-ok:#00e676;
  --ap-err:#ff4560;
  --ap-warn:#fbbf24;
  --ap-grad:linear-gradient(135deg,#f0a500,#c084fc);
}

.ap-header{
  background:linear-gradient(135deg,rgba(240,165,0,.07) 0%,rgba(192,132,252,.07) 100%);
  border:1px solid rgba(240,165,0,.18);
  border-radius:16px;
  padding:24px 28px;
  margin-bottom:24px;
  position:relative;
  overflow:hidden;
}

.ap-header::before{
  content:'';
  position:absolute;
  top:0;left:0;right:0;
  height:2px;
  background:var(--ap-grad);
}

.ap-title{
  font-family:'Syne',sans-serif;
  font-size:26px;
  font-weight:900;
  background:var(--ap-grad);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  margin-bottom:4px;
}

.ap-subtitle{
  font-family:'JetBrains Mono',monospace;
  font-size:11px;
  color:var(--muted);
}

.ap-pipeline{
  display:flex;
  align-items:center;
  gap:0;
  background:var(--dim);
  border:1px solid var(--border);
  border-radius:12px;
  padding:16px 20px;
  margin-bottom:24px;
  overflow-x:auto;
}

.ap-step{
  display:flex;
  flex-direction:column;
  align-items:center;
  gap:6px;
  min-width:80px;
  text-align:center;
  position:relative;
}

.ap-step-icon{
  width:36px;
  height:36px;
  border-radius:10px;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:16px;
  background:rgba(255,255,255,.05);
  border:1px solid var(--border);
  transition:all .3s;
  position:relative;
  z-index:1;
}

.ap-step.active .ap-step-icon{
  background:var(--ap-grad);
  border-color:var(--ap-gold);
  box-shadow:0 0 20px rgba(240,165,0,.3);
}

.ap-step-label{
  font-size:10px;
  font-weight:600;
  color:var(--muted);
  text-transform:uppercase;
  letter-spacing:1px;
}

.ap-step.active .ap-step-label{
  color:#fff;
}

.ap-step-connector{
  width:40px;
  height:2px;
  background:var(--border);
  margin:0 -8px;
}

.ap-step.active + .ap-step-connector{
  background:var(--ap-gold);
}

.job-card{
  background:var(--dim);
  border:1px solid var(--border);
  border-radius:12px;
  padding:20px;
  margin-bottom:16px;
  transition:all 0.3s;
}

.job-card:hover{
  border-color:var(--ap-gold);
  box-shadow:0 4px 12px rgba(240,165,0,.1);
}

.btn-sm{
  padding:8px 16px;
  font-size:12px;
}
</style>
CSS;
    }
}
