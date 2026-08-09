<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="description" content="Calculate the Abjad (Huroof-e-Abjad) values of Urdu, Persian, and Arabic names or words instantly. Free online calculator featuring real-time letter breakdown, digital roots, and search suggestions.">
    <meta name="keywords" content="Abjad calculator, Huroof-e-Abjad, Urdu abjad calculator, Arabic abjad, Persian abjad, abjad calculations, letter value calculator, digital root, islamic name calculator">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://apps.techinbox.ie/abjad/">
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://apps.techinbox.ie/abjad/">
    <meta property="og:title" content="Huroof-e-Abjad Computation | Abjad Calculator">
    <meta property="og:description" content="Calculate the Abjad values of Urdu, Persian, and Arabic words instantly with complete letter breakdowns, digital roots, and suggestions.">
    <meta property="og:image" content="https://apps.techinbox.ie/public/icons/site.png">
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://apps.techinbox.ie/abjad/">
    <meta property="twitter:title" content="Huroof-e-Abjad Computation | Abjad Calculator">
    <meta property="twitter:description" content="Calculate the Abjad values of Urdu, Persian, and Arabic words instantly with complete letter breakdowns, digital roots, and suggestions.">
    <meta property="twitter:image" content="https://apps.techinbox.ie/public/icons/site.png">
    <title>Huroof-e-Abjad Computation | Abjad Calculator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0b0f19;
            --card-bg: rgba(22, 28, 45, 0.6);
            --card-border: rgba(255, 255, 255, 0.08);
            --primary-glow: rgba(99, 102, 241, 0.15);
            --accent-color: #6366f1;
            --accent-glow: #4f46e5;
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --gold-accent: #f59e0b;
            --gold-glow: rgba(245, 158, 11, 0.2);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: 'Outfit', sans-serif;
            min-height: 100vh;
            padding: 2rem 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            background-image: 
                radial-gradient(at 0% 0%, rgba(30, 27, 75, 0.4) 0, transparent 50%),
                radial-gradient(at 100% 0%, rgba(17, 24, 39, 0.6) 0, transparent 50%),
                radial-gradient(at 50% 100%, rgba(99, 102, 241, 0.1) 0, transparent 50%);
            background-attachment: fixed;
        }

        header {
            text-align: center;
            margin-bottom: 2.5rem;
            max-width: 800px;
        }

        h1 {
            font-size: 1.4rem;
            font-weight: 700;
            background: linear-gradient(135deg, #fff 30%, #a5b4fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            display: flex;
            flex-direction: column;
            gap: 2.5rem;
        }

        /* Calculator Section */
        .calculator-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 2rem;
            backdrop-filter: blur(16px);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            position: relative;
            overflow: hidden;
            width: 100%;
            max-width: 756px;
            margin: 0 auto;
        }

        .calculator-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--accent-color), transparent);
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .calc-input {
            width: 100%;
            padding: 0.75rem 1.25rem;
            background: rgba(17, 24, 39, 0.7);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            color: var(--text-main);
            font-family: 'Amiri', serif;
            font-size: 1.4rem;
            direction: rtl;
            transition: all 0.3s ease;
        }

        .calc-input:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 4px var(--primary-glow);
        }

        .calc-input::placeholder {
            font-family: 'Outfit', sans-serif;
            font-size: 1rem;
            direction: ltr;
            text-align: left;
            color: rgba(156, 163, 175, 0.4);
        }

        /* Detail input fields */
        .detail-input {
            direction: auto;
            text-align: right;
        }

        .calc-results {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            align-items: center;
            justify-content: space-between;
            background: rgba(17, 24, 39, 0.4);
            padding: 1.25rem;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.03);
        }

        .values-wrapper {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            align-items: center;
            flex-shrink: 0;
        }

        .total-value-container {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
        }

        .total-value-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
        }

        .total-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--gold-accent);
            line-height: 1;
            text-shadow: 0 0 10px var(--gold-glow);
        }

        .breakdown-container {
            flex: 1;
            min-width: 150px;
        }

        .breakdown-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-bottom: 0.4rem;
        }

        .breakdown-flow {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            direction: rtl;
            align-items: center;
        }

        .breakdown-item {
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.2);
            padding: 0.35rem 0.5rem;
            border-radius: 6px;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 38px;
        }

        .breakdown-letter {
            font-family: 'Amiri', serif;
            font-size: 1.1rem;
            font-weight: bold;
            color: var(--text-main);
        }

        .breakdown-val {
            font-size: 0.65rem;
            color: var(--text-muted);
        }

        .breakdown-space-separator {
            width: 1rem;
            height: 1.8rem;
            border-left: 2px dashed rgba(255, 255, 255, 0.15);
            margin: 0 0.35rem;
            display: inline-block;
        }

        /* Buttons */
        .btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--card-border);
            color: var(--text-main);
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            font-family: 'Outfit', sans-serif;
            transition: all 0.2s ease;
        }

        .btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .btn-primary {
            background: var(--accent-color);
            border-color: var(--accent-color);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .btn-primary:hover {
            background: var(--accent-glow);
            border-color: var(--accent-glow);
        }

        .btn-danger {
            background: rgba(239, 68, 68, 0.15);
            border-color: rgba(239, 68, 68, 0.3);
            color: #f87171;
            transition: all 0.2s ease;
        }

        .btn-danger:hover {
            background: rgba(239, 68, 68, 0.25);
            border-color: rgba(239, 68, 68, 0.45);
        }

        /* Modal Overlay & Saved History Modal Popup */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(11, 15, 25, 0.85);
            backdrop-filter: blur(8px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 1rem;
        }

        .modal-content {
            background: rgba(22, 28, 45, 0.95);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            width: 100%;
            max-width: 850px;
            padding: 2.0rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            position: relative;
            max-height: 92vh;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding-bottom: 1rem;
            direction: rtl; /* Form headers right-to-left */
        }

        .modal-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .modal-close {
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 1.8rem;
            cursor: pointer;
            transition: color 0.2s;
            line-height: 1;
        }

        .modal-close:hover {
            color: #f87171;
        }

        /* History Table Styling: Configured for RTL layout & Grid lines */
        .history-table-wrapper {
            overflow-x: auto;
            max-height: 60vh;
            border-radius: 12px;
            border: 1px solid var(--card-border);
            background: rgba(11, 15, 25, 0.4);
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            direction: rtl; /* Right-to-Left arrangement */
            text-align: right;
            font-size: 0.9rem;
        }

        .history-table th {
            background: rgba(22, 28, 45, 0.95);
            color: var(--text-main);
            font-weight: 800; /* Bold headers */
            padding: 0.75rem 1rem;
            border: 1px solid var(--card-border); /* Visible grid lines */
            user-select: none;
        }

        .history-table thead tr:first-child th {
            position: sticky;
            top: 0;
            z-index: 12;
            padding: 6px 10px;
            height: 40px;
        }

        .history-table thead tr:nth-child(2) th {
            position: sticky;
            top: 40px;
            z-index: 11;
        }

        .history-table th.sortable:hover {
            background: rgba(99, 102, 241, 0.15);
            color: var(--text-main);
            cursor: pointer;
        }

        .history-table td {
            padding: 0.75rem 1rem;
            border: 1px solid var(--card-border); /* Visible grid lines */
            color: var(--text-main);
            vertical-align: middle;
        }

        /* Alternate color rows (zebra grid) */
        .history-table tbody tr:nth-child(even) {
            background: rgba(255, 255, 255, 0.03);
        }
        .history-table tbody tr:nth-child(odd) {
            background: rgba(255, 255, 255, 0.005);
        }

        .history-table tr:hover {
            background: rgba(99, 102, 241, 0.05);
        }

        .history-table td.arabic-cell {
            font-family: 'Amiri', serif;
            font-size: 1.3rem;
            direction: rtl;
        }

        .sort-icon {
            font-size: 0.75rem;
            margin-right: 0.25rem;
            color: var(--text-muted);
        }

        /* Table Column search inputs */
        .table-search-input {
            width: 100%;
            padding: 0.35rem 0.5rem;
            background: rgba(11, 15, 25, 0.8);
            border: 1px solid var(--card-border);
            border-radius: 6px;
            color: var(--text-main);
            font-size: 0.8rem;
            outline: none;
            direction: rtl;
            transition: border-color 0.2s;
        }

        .table-search-input:focus {
            border-color: var(--accent-color);
            background: rgba(17, 24, 39, 0.95);
        }

        /* Floating, Draggable style for modal keyboard copy */
        .floating-keyboard {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            width: auto;
            max-width: 440px;
            background: rgba(15, 23, 42, 0.98) !important;
            border: 2px solid var(--accent-color) !important;
            border-radius: 16px !important;
            padding: 1rem !important;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.95) !important;
            z-index: 1010;
            cursor: move; /* Drag cursor hint */
        }

        /* Reduce the keyboard's grid boxes just to 2px more than the size of urdu alphabets */
        .floating-keyboard .letters-grid {
            max-width: 360px !important;
            gap: 0.4rem !important;
        }

        .floating-keyboard .letter-card {
            width: 40px !important;
            height: 40px !important;
            aspect-ratio: 1/1 !important;
            border-radius: 6px !important;
        }

        .floating-keyboard .letter-card .letter-arabic {
            font-size: 1.6rem !important;
            line-height: 1 !important;
        }

        /* Grid Section styled with Flexbox for center aligning the last row */
        .chart-section {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .letters-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.6rem;
            direction: rtl;
            max-width: 640px;
            margin: 0 auto;
        }

        .letter-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(8px);
            cursor: pointer;
            user-select: none;
            width: 71px;
            height: 71px;
            aspect-ratio: 1 / 1;
            overflow: hidden;
        }

        .letter-card:hover {
            transform: translateY(-2px);
            border-color: rgba(99, 102, 241, 0.4);
            box-shadow: 0 6px 12px -3px rgba(99, 102, 241, 0.15);
        }

        .letter-card:active {
            transform: scale(0.95);
        }

        .letter-card.highlighted {
            background: rgba(99, 102, 241, 0.18);
            border-color: var(--accent-color);
            box-shadow: 0 0 12px rgba(99, 102, 241, 0.3);
        }

        /* Keyboard Modes: Arabic letters only (without bottom values) */
        .letters-grid.alphabets-only .letter-card .letter-value {
            display: none !important;
        }

        .letters-grid.alphabets-only .letter-card .letter-arabic {
            position: static;
            font-size: 2.3rem;
            line-height: 1;
            margin: 0;
            padding: 0;
        }

        .letters-grid.alphabets-only .letter-card {
            align-items: center;
            justify-content: center;
        }

        .letter-arabic {
            position: absolute;
            top: 9px;
            right: 8px;
            font-family: 'Amiri', serif;
            font-size: 2.1rem;
            line-height: 0.95;
            color: #fff;
            margin: 0;
            padding: 0;
        }

        /* Absolutely positioned to bottom left corner */
        .letter-value {
            position: absolute;
            bottom: 5px;
            left: 7px;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--gold-accent);
            line-height: 1;
        }

        /* Mobile Responsiveness Viewports */
        @media (max-width: 768px) {
            body {
                padding: 1rem 0.5rem;
            }
            header {
                margin-bottom: 1.5rem;
            }
            h1 {
                font-size: 1.25rem;
            }
            .container {
                gap: 1.5rem;
            }
            .calculator-card {
                padding: 1.25rem;
                border-radius: 16px;
                gap: 1.25rem;
            }
            .calc-results {
                flex-direction: column;
                align-items: stretch;
                padding: 1rem;
                gap: 1rem;
            }
            .values-wrapper {
                justify-content: center;
                margin-bottom: 0.5rem;
                gap: 1.5rem;
            }
            .letters-grid {
                max-width: 100%;
                gap: 0.5rem;
            }
            .letter-card {
                width: 60px;
                height: 60px;
            }
            .modal-content {
                padding: 1.25rem;
                border-radius: 16px;
                gap: 1.25rem;
                max-height: 95vh;
            }
            .floating-keyboard {
                width: 92%;
                bottom: 1rem;
                max-width: 360px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 0.75rem 0.25rem;
            }
            header {
                margin-bottom: 1rem;
            }
            h1 {
                font-size: 1.1rem;
            }
            .calculator-card {
                padding: 1rem;
                border-radius: 14px;
                gap: 1rem;
            }
            .calc-input {
                padding: 0.65rem 1rem;
                font-size: 1.2rem;
            }
            .calc-results {
                padding: 0.75rem;
                gap: 0.75rem;
            }
            .values-wrapper {
                gap: 1rem;
            }
            .total-value {
                font-size: 1.5rem;
            }
            .letters-grid {
                gap: 0.35rem;
            }
            .letter-card {
                width: 48px;
                height: 48px;
                border-radius: 8px;
            }
            .letter-arabic {
                font-size: 1.4rem;
            }
            .letter-value {
                font-size: 0.65rem;
                bottom: 2px;
                left: 4px;
            }
            .btn {
                padding: 0.5rem 0.85rem;
                font-size: 0.8rem;
            }
            .floating-keyboard .letter-card {
                width: 36px !important;
                height: 36px !important;
            }
            .floating-keyboard .letter-card .letter-arabic {
                font-size: 1.3rem !important;
            }
        }

        /* Smooth transitions for theme toggle */
        body, .calculator-card, .calc-input, .btn, .letter-card, .modal-content, .history-table th, .history-table td, .history-table-wrapper, .table-search-input {
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease, box-shadow 0.3s ease, background-image 0.3s ease;
        }

        /* Soft Mode Styles */
        body.soft-mode {
            --bg-color: #f3f4f6;
            --card-bg: rgba(255, 255, 255, 0.75);
            --card-border: rgba(0, 0, 0, 0.08);
            --primary-glow: rgba(99, 102, 241, 0.1);
            --accent-color: #4f46e5;
            --accent-glow: #3730a3;
            --text-main: #1f2937;
            --text-muted: #6b7280;
            --gold-accent: #d97706;
            --gold-glow: rgba(217, 119, 6, 0.15);
            background-image: 
                radial-gradient(at 0% 0%, rgba(224, 231, 255, 0.6) 0, transparent 50%),
                radial-gradient(at 100% 0%, rgba(243, 244, 246, 0.8) 0, transparent 50%),
                radial-gradient(at 50% 100%, rgba(99, 102, 241, 0.05) 0, transparent 50%);
        }

        body.soft-mode h1 {
            background: linear-gradient(135deg, #1f2937 30%, #4f46e5 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        body.soft-mode .calc-input {
            background: rgba(255, 255, 255, 0.85);
            color: #1f2937;
            border-color: rgba(0, 0, 0, 0.1);
        }

        body.soft-mode .calc-input::placeholder {
            color: rgba(107, 114, 128, 0.5);
        }

        body.soft-mode .calc-results {
            background: rgba(243, 244, 246, 0.6);
            border-color: rgba(0, 0, 0, 0.04);
        }

        body.soft-mode .letter-arabic {
            color: #1f2937;
        }

        body.soft-mode .letter-card:hover {
            border-color: rgba(99, 102, 241, 0.6);
            box-shadow: 0 6px 12px -3px rgba(99, 102, 241, 0.25);
        }

        body.soft-mode .modal-content {
            background: rgba(255, 255, 255, 0.98);
            border-color: rgba(0, 0, 0, 0.1);
        }

        body.soft-mode .modal-title {
            color: #1f2937;
        }

        body.soft-mode .history-table-wrapper {
            background: rgba(243, 244, 246, 0.6);
        }

        body.soft-mode .history-table th {
            background: rgba(255, 255, 255, 0.95);
            color: #1f2937;
            border-color: rgba(0, 0, 0, 0.08);
        }

        body.soft-mode .history-table td {
            color: #1f2937;
            border-color: rgba(0, 0, 0, 0.08);
        }

        body.soft-mode .history-table tbody tr:nth-child(even) {
            background: rgba(0, 0, 0, 0.015);
        }
        body.soft-mode .history-table tbody tr:nth-child(odd) {
            background: rgba(0, 0, 0, 0.003);
        }

        body.soft-mode .table-search-input {
            background: rgba(255, 255, 255, 0.9);
            color: #1f2937;
            border-color: rgba(0, 0, 0, 0.1);
        }

        body.soft-mode .table-search-input:focus {
            background: #fff;
            border-color: var(--accent-color);
        }

        body.soft-mode .floating-keyboard {
            background: rgba(255, 255, 255, 0.98) !important;
            border: 2px solid var(--accent-color) !important;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15) !important;
        }

        body.soft-mode #addEditRecordForm {
            background: rgba(243, 244, 246, 0.6);
            border-color: rgba(0, 0, 0, 0.1);
        }

        body.soft-mode #addEditRecordForm label {
            color: #000000 !important;
            font-weight: bold !important;
        }

        body.soft-mode #formTitle {
            color: #1f2937;
        }

        body.soft-mode .btn-primary {
            color: #ffffff;
        }

        body.soft-mode .btn:not(.btn-primary) {
            color: #374151;
            border-color: rgba(0, 0, 0, 0.15);
        }

        body.soft-mode .btn-danger {
            background: rgba(220, 38, 38, 0.08);
            border-color: rgba(220, 38, 38, 0.2);
            color: #dc2626;
        }

        body.soft-mode .btn-danger:hover {
            background: rgba(220, 38, 38, 0.15);
            border-color: rgba(220, 38, 38, 0.3);
        }

        body.soft-mode #btnBackspace, body.soft-mode #modalBtnBackspace {
            color: #dc2626;
            border-color: rgba(220, 38, 38, 0.2);
            background: rgba(220, 38, 38, 0.08);
        }

        /* Suggestions Dropdown Style */
        .suggestions-box {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: rgba(15, 23, 42, 0.95);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            margin-top: 0.35rem;
            max-height: 200px;
            overflow-y: auto;
            z-index: 100;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(12px);
            direction: rtl;
        }

        .suggestion-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            transition: background 0.2s ease;
        }

        .suggestion-item:last-child {
            border-bottom: none;
        }

        .suggestion-item:hover {
            background: rgba(99, 102, 241, 0.15);
        }

        .suggestion-name {
            font-family: 'Amiri', serif;
            font-size: 1.25rem;
            color: var(--text-main);
        }

        .suggestion-details {
            font-size: 0.8rem;
            color: var(--text-muted);
            display: flex;
            gap: 0.75rem;
            align-items: center;
            direction: ltr;
        }

        .suggestion-val {
            color: var(--gold-accent);
            font-weight: 700;
        }

        /* Soft mode compatibility */
        body.soft-mode .suggestions-box {
            background: rgba(255, 255, 255, 0.98);
            border-color: rgba(0, 0, 0, 0.1);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        body.soft-mode .suggestion-item {
            border-bottom-color: rgba(0, 0, 0, 0.05);
        }
        body.soft-mode .suggestion-item:hover {
            background: rgba(99, 102, 241, 0.08);
        }
        body.soft-mode .suggestion-name {
            color: #1f2937;
        }
    </style>
</head>
<body>

    <header style="display: flex; align-items: center; justify-content: center; gap: 0.75rem; margin-bottom: 2.5rem; position: relative;">
        <h1 style="margin-bottom: 0;">Huroof-e-Abjad Computation</h1>
        <button id="btnThemeToggle" class="btn" aria-label="Toggle Theme" style="padding: 0; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--card-border); cursor: pointer;">
            <span id="themeToggleIcon" style="font-size: 1.1rem; line-height: 1;">🌗</span>
        </button>
    </header>

    <main class="container">
        
        <!-- Interactive Calculator -->
        <div class="calculator-card">
            
            <!-- Main Name Field -->
            <div class="input-group" style="position: relative;">
                <div class="input-wrapper">
                    <input type="text" id="calcInput" class="calc-input" placeholder="Type Urdu text here or click reference grid boxes below to input..." autocomplete="off">
                </div>
                <div id="suggestionsBox" class="suggestions-box" style="display: none;"></div>
            </div>

            <!-- Optional Fields Row: Buttons, Origin and Meanings (Meaning fills the rest of the space) -->
            <div style="display: flex; gap: 0.5rem; width: 100%; flex-wrap: wrap; align-items: flex-end;">
                <button id="btnClear" class="btn btn-primary" style="padding: 0.6rem 0.8rem; margin: 0; font-size: 0.85rem; height: 38px; border-radius: 10px;">Clear</button>
                <button id="btnSave" class="btn btn-primary" style="padding: 0.6rem 0.8rem; margin: 0; font-size: 0.85rem; height: 38px; border-radius: 10px;">Save</button>
                <button id="btnMemo" class="btn btn-primary" style="padding: 0.6rem 0.8rem; margin: 0; font-size: 0.85rem; height: 38px; border-radius: 10px;">Memo</button>
                
                <div style="width: 100px; display: flex; flex-direction: column; gap: 0.3rem; flex-shrink: 0;">
                    <label style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">Origin</label>
                    <input type="text" id="originInput" class="calc-input detail-input" placeholder="Origin..." style="padding: 0.5rem 0.6rem; font-size: 0.9rem; border-radius: 10px; height: 38px;">
                </div>
                
                <div style="flex: 1; min-width: 200px; display: flex; flex-direction: column; gap: 0.3rem;">
                    <label style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">Meanings (Optional)</label>
                    <input type="text" id="meaningInput" class="calc-input detail-input" placeholder="e.g. Gracious, King..." style="padding: 0.5rem 0.75rem; font-size: 0.9rem; border-radius: 10px; height: 38px;">
                </div>
            </div>

            <div class="calc-results">
                <!-- Swapped order so Single is on the left and Total is on the right -->
                <div class="values-wrapper">
                    <div class="total-value-container">
                        <div class="total-value-label">Single</div>
                        <div class="total-value" id="singleValue" style="color: var(--accent-color); text-shadow: 0 0 10px rgba(99, 102, 241, 0.4);">0</div>
                    </div>
                    <div class="total-value-container" style="border-left: 1px solid rgba(255, 255, 255, 0.1); padding-left: 1.5rem;">
                        <div class="total-value-label">Total</div>
                        <div class="total-value" id="totalValue">0</div>
                    </div>
                </div>
                
                <div class="breakdown-container">
                    <div class="breakdown-label">Character Breakdown</div>
                    <div class="breakdown-flow" id="breakdownFlow">
                        <span style="color: var(--text-muted); font-size: 0.8rem; font-style: italic;">Start typing or clicking letters to see breakdown...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Page Keyboard (Remains visible at all times, has NO numeric row, shows letter values) -->
        <div class="chart-section" id="keyboardContainer">
            <div class="section-header" style="flex-direction: column; align-items: stretch; gap: 0.75rem;">
                <!-- Action Row (Space bar & Backspace, aligned with width of characters grid) -->
                <div style="display: flex; gap: 0.6rem; width: 100%; max-width: 640px; margin: 0 auto;">
                    <button id="btnSpaceBar" class="btn btn-primary" style="flex: 1; margin: 0; padding: 0.75rem 0; text-align: center;">Space Bar ␣</button>
                    <button id="btnBackspace" class="btn" style="flex: 1; margin: 0; padding: 0.75rem 0; text-align: center; background: rgba(239, 68, 68, 0.15); border-color: rgba(239, 68, 68, 0.3); color: #f87171;">Backspace ⌫</button>
                </div>
            </div>
            
            <div class="letters-grid" id="lettersGrid">
                <!-- Cards will be dynamically injected by JS -->
            </div>
        </div>

    </div>

    <!-- Saved History Modal Popup -->
    <div id="memoModal" class="modal-overlay">
        <div class="modal-content">
            <!-- Modal Header (RTL order: Add New and Show Keyboard side-by-side on right, title, Close button) -->
            <div class="modal-header">
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <button id="btnAddNew" class="btn btn-primary" style="font-size: 0.85rem; padding: 0.5rem 1rem;">Add New</button>
                    <!-- Keyboard Toggle Button relocated beside Add New button -->
                    <button id="btnToggleKeyboard" class="btn" style="font-size: 0.85rem; padding: 0.5rem 1rem; border-color: var(--accent-color); color: var(--text-main);">Show Keyboard</button>
                </div>
                
                <span class="modal-title" style="margin-right: auto;">💾 Saved History</span>
                <button class="modal-close" id="btnCloseModal">&times;</button>
            </div>
            
            <!-- Manual Add/Edit Form Container (hidden by default) -->
            <div id="addEditRecordForm" style="display: none; background: rgba(11, 15, 25, 0.5); padding: 1.25rem; border-radius: 12px; border: 1px solid var(--card-border); margin-bottom: 0.5rem;">
                <h3 id="formTitle" style="font-size: 1.1rem; color: var(--text-main); margin-bottom: 1rem; text-align: center;">Add New Record</h3>
                <input type="hidden" id="editRecordId">
                <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; direction: rtl;">
                    <div style="flex: 1.5; min-width: 150px; display: flex; flex-direction: column; gap: 0.3rem;">
                        <label style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500; text-align: center;">Name</label>
                        <input type="text" id="formName" class="calc-input" placeholder="e.g. احمد" style="height: 38px; font-size: 1rem; padding: 0.5rem 0.75rem;">
                    </div>
                    <!-- numeric fields total & single, formatted LTR -->
                    <div style="width: 90px; display: flex; flex-direction: column; gap: 0.3rem;">
                        <label style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500; text-align: center;">Total</label>
                        <input type="text" id="formTotal" class="calc-input" style="height: 38px; font-size: 1.1rem; padding: 0.5rem 0.75rem; direction: ltr; text-align: left;">
                    </div>
                    <div style="width: 90px; display: flex; flex-direction: column; gap: 0.3rem;">
                        <label style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500; text-align: center;">Single</label>
                        <input type="text" id="formSingle" class="calc-input" style="height: 38px; font-size: 1.1rem; padding: 0.5rem 0.75rem; direction: ltr; text-align: left;">
                    </div>
                    <div style="width: 120px; display: flex; flex-direction: column; gap: 0.3rem;">
                        <label style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500; text-align: center;">Origin</label>
                        <input type="text" id="formOrigin" class="calc-input" placeholder="Origin" style="height: 38px; font-size: 0.95rem; padding: 0.5rem 0.75rem; direction: auto; text-align: right;">
                    </div>
                    <div style="flex: 2; min-width: 180px; display: flex; flex-direction: column; gap: 0.3rem;">
                        <label style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500; text-align: center;">Meanings</label>
                        <input type="text" id="formMeanings" class="calc-input" placeholder="Meanings" style="height: 38px; font-size: 0.95rem; padding: 0.5rem 0.75rem; direction: auto; text-align: right;">
                    </div>
                </div>
                <div style="display: flex; gap: 0.5rem; justify-content: flex-start; margin-top: 1rem; direction: rtl;">
                    <button id="btnSubmitForm" class="btn btn-primary" style="height: 36px; padding: 0 1.25rem; font-size: 0.85rem;">Save Record</button>
                    <button id="btnCancelForm" class="btn" style="height: 36px; padding: 0 1.25rem; font-size: 0.85rem;">Cancel</button>
                </div>
            </div>
            
            <!-- Saved Calculations Table -->
            <div class="history-table-wrapper">
                <table class="history-table">
                    <thead>
                        <!-- Row 1: Search Inputs, with numeric columns search-total & search-single styled LTR -->
                        <tr>
                            <th style="width: 20%;"><input type="text" class="table-search-input" id="search-name" placeholder="Search Name..."></th>
                            <th style="width: 12%;"><input type="text" class="table-search-input" id="search-total" placeholder="Total..." style="direction: ltr; text-align: left;"></th>
                            <th style="width: 12%;"><input type="text" class="table-search-input" id="search-single" placeholder="Single..." style="direction: ltr; text-align: left;"></th>
                            <th style="width: 18%;"><input type="text" class="table-search-input" id="search-origin" placeholder="Search Origin..."></th>
                            <th style="width: 23%;"><input type="text" class="table-search-input" id="search-meanings" placeholder="Search Meanings..."></th>
                            <th style="width: 15%; text-align: center;"><button id="btnClearFilters" class="btn" style="font-size: 0.75rem; padding: 0.3rem 0.6rem; border-color: rgba(239, 68, 68, 0.4); color: #f87171; display: none;">Clear</button></th>
                        </tr>
                        <!-- Row 2: Sortable Column Headers -->
                        <tr id="sortHeaderRow">
                            <th class="sortable" data-col="name" style="width: 20%;">Name <span id="sort-icon-name" class="sort-icon">⇅</span></th>
                            <th class="sortable" data-col="total" style="width: 12%;">Total <span id="sort-icon-total" class="sort-icon">⇅</span></th>
                            <th class="sortable" data-col="single" style="width: 12%;">Single <span id="sort-icon-single" class="sort-icon">⇅</span></th>
                            <th class="sortable" data-col="origin" style="width: 18%;">Origin <span id="sort-icon-origin" class="sort-icon">⇅</span></th>
                            <th class="sortable" data-col="meanings" style="width: 23%;">Meanings <span id="sort-icon-meanings" class="sort-icon">⇅</span></th>
                            <th style="width: 15%; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="historyTableBody">
                        <!-- Populated dynamically -->
                    </tbody>
                </table>
            </div>

            <!-- Floating & Moveable Modal Keyboard Copy (Urdu alphabets only, numeric row on top) -->
            <div class="chart-section floating-keyboard" id="modalKeyboardContainer" style="display: none;">
                <div class="section-header" style="flex-direction: column; align-items: stretch; gap: 0.75rem;">
                    <!-- Top Numeric Row -->
                    <div id="modalKeyboardNumericRow" style="display: flex; gap: 0.4rem; justify-content: center; margin: 0 auto; direction: ltr; width: 100%; max-width: 640px;"></div>
                    
                    <!-- Action Row (Space bar & Backspace inside modal) -->
                    <div style="display: flex; gap: 0.4rem; width: 100%; max-width: 640px; margin: 0 auto;">
                        <button id="modalBtnSpaceBar" class="btn btn-primary" style="flex: 1; margin: 0; padding: 0.5rem 0; text-align: center; font-size: 0.8rem;">Space ␣</button>
                        <button id="modalBtnBackspace" class="btn" style="flex: 1; margin: 0; padding: 0.5rem 0; text-align: center; background: rgba(239, 68, 68, 0.15); border-color: rgba(239, 68, 68, 0.3); color: #f87171; font-size: 0.8rem;">Delete ⌫</button>
                    </div>
                </div>
                
                <div class="letters-grid alphabets-only" id="modalLettersGrid">
                    <!-- Cards will be dynamically injected by JS -->
                </div>
            </div>

        </div>
    </main>

    <script>
        // Data containing all individual letters and their values (split where appropriate)
        const letterData = [
            { char: 'ا', value: 1, name: 'Alif' },
            { char: 'آ', value: 1, name: 'Alif Mad' },
            { char: 'ب', value: 2, name: 'Be' },
            { char: 'پ', value: 2, name: 'Pe' },
            { char: 'ج', value: 3, name: 'Jeem' },
            { char: 'چ', value: 3, name: 'Che' },
            { char: 'د', value: 4, name: 'Dal' },
            { char: 'ڈ', value: 4, name: 'Ddal' },
            { char: 'ہ', value: 5, name: 'Gol He' },
            { char: 'ھ', value: 5, name: 'Do-chashmi He' },
            { char: 'و', value: 6, name: 'Wao' },
            { char: 'ز', value: 7, name: 'Ze' },
            { char: 'ژ', value: 7, name: 'Zhe' },
            { char: 'ح', value: 8, name: 'He (Halqi)' },
            { char: 'ط', value: 9, name: 'To\'ey' },
            { char: 'ی', value: 10, name: 'Ye' },
            { char: 'ے', value: 10, name: 'Bari Ye' },
            { char: 'ک', value: 20, name: 'Kaaf' },
            { char: 'گ', value: 20, name: 'Gaaf' },
            { char: 'ل', value: 30, name: 'Laam' },
            { char: 'م', value: 40, name: 'Meem' },
            { char: 'ن', value: 50, name: 'Noon' },
            { char: 'ں', value: 50, name: 'Noon Ghunna' },
            { char: 'س', value: 60, name: 'Seen' },
            { char: 'ع', value: 70, name: 'Ain' },
            { char: 'ف', value: 80, name: 'Fe' },
            { char: 'ص', value: 90, name: 'Saad' },
            { char: 'ق', value: 100, name: 'Qaaf' },
            { char: 'ر', value: 200, name: 'Re' },
            { char: 'ڑ', value: 200, name: 'Rre' },
            { char: 'ش', value: 300, name: 'Sheen' },
            { char: 'ت', value: 400, name: 'Te' },
            { char: 'ٹ', value: 400, name: 'Tte' },
            { char: 'ث', value: 500, name: 'Se' },
            { char: 'خ', value: 600, name: 'Khe' },
            { char: 'ذ', value: 700, name: 'Zaal' },
            { char: 'ض', value: 800, name: 'Zwaad' },
            { char: 'ظ', value: 900, name: 'Zo\'ey' },
            { char: 'غ', value: 1000, name: 'Ghain' }
        ];

        // Mapping helper for fast dictionary lookup
        const letterMap = {};
        letterData.forEach(item => {
            letterMap[item.char] = item.value;
        });

        // Regex pattern containing all allowed Urdu letters, space, and Hamza variations
        const allowedUrduRegex = /[^\sاآبپتٹثجچحخدڈذرڑزژسشصضطظعغفقکگلمنںوہھءیے]/g;

        // Global reference tracking which text box is currently focused. Defaults to Name field.
        let activeInputField = document.getElementById('calcInput');

        // Global cache of calculations downloaded from server
        let calculationsHistory = [];
        
        // Sorting State
        let currentSortCol = null;
        let currentSortDir = 'desc';

        // Filter State
        const currentFilters = {
            name: '',
            total: '',
            single: '',
            origin: '',
            meanings: ''
        };

        // Make floating element draggable by mouse or touch
        function makeElementDraggable(elm) {
            let pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
            elm.onmousedown = dragMouseDown;
            elm.ontouchstart = dragTouchStart;

            function dragMouseDown(e) {
                e = e || window.event;
                if (e.target.tagName === 'BUTTON' || e.target.classList.contains('letter-card') || e.target.classList.contains('letter-arabic')) return;
                e.preventDefault();
                pos3 = e.clientX;
                pos4 = e.clientY;
                document.onmouseup = closeDragElement;
                document.onmousemove = elementDrag;
            }

            function elementDrag(e) {
                e = e || window.event;
                e.preventDefault();
                pos1 = pos3 - e.clientX;
                pos2 = pos4 - e.clientY;
                pos3 = e.clientX;
                pos4 = e.clientY;
                
                let newTop = elm.offsetTop - pos2;
                let newLeft = elm.offsetLeft - pos1;
                
                elm.style.top = newTop + "px";
                elm.style.left = newLeft + "px";
                elm.style.bottom = "auto";
                elm.style.transform = "none";
            }

            function closeDragElement() {
                document.onmouseup = null;
                document.onmousemove = null;
            }

            function dragTouchStart(e) {
                if (e.target.tagName === 'BUTTON' || e.target.classList.contains('letter-card') || e.target.classList.contains('letter-arabic')) return;
                const touch = e.touches[0];
                pos3 = touch.clientX;
                pos4 = touch.clientY;
                document.ontouchend = closeTouchDrag;
                document.ontouchmove = touchDrag;
            }

            function touchDrag(e) {
                const touch = e.touches[0];
                pos1 = pos3 - touch.clientX;
                pos2 = pos4 - touch.clientY;
                pos3 = touch.clientX;
                pos4 = touch.clientY;

                let newTop = elm.offsetTop - pos2;
                let newLeft = elm.offsetLeft - pos1;

                elm.style.top = newTop + "px";
                elm.style.left = newLeft + "px";
                elm.style.bottom = "auto";
                elm.style.transform = "none";
            }

            function closeTouchDrag() {
                document.ontouchend = null;
                document.ontouchmove = null;
            }
        }

        // Show Keyboard function helper
        function showModalKeyboard() {
            const modalKeyboardContainer = document.getElementById('modalKeyboardContainer');
            const btnToggleKeyboard = document.getElementById('btnToggleKeyboard');
            if (modalKeyboardContainer.style.display === 'none') {
                modalKeyboardContainer.style.top = "";
                modalKeyboardContainer.style.left = "50%";
                modalKeyboardContainer.style.transform = "translateX(-50%)";
                modalKeyboardContainer.style.display = 'flex';
                btnToggleKeyboard.textContent = 'Hide Keyboard';
            }
        }

        // Track focus on all editable fields on main page
        document.getElementById('calcInput').addEventListener('focus', (e) => { activeInputField = e.target; });
        document.getElementById('originInput').addEventListener('focus', (e) => { activeInputField = e.target; });
        document.getElementById('meaningInput').addEventListener('focus', (e) => { activeInputField = e.target; });

        // Track focus on all editable fields inside the modal form
        document.getElementById('formName').addEventListener('focus', (e) => { activeInputField = e.target; showModalKeyboard(); });
        document.getElementById('formTotal').addEventListener('focus', (e) => { activeInputField = e.target; showModalKeyboard(); });
        document.getElementById('formSingle').addEventListener('focus', (e) => { activeInputField = e.target; showModalKeyboard(); });
        document.getElementById('formOrigin').addEventListener('focus', (e) => { activeInputField = e.target; showModalKeyboard(); });
        document.getElementById('formMeanings').addEventListener('focus', (e) => { activeInputField = e.target; showModalKeyboard(); });

        // Track focus on search input fields in the table header
        document.getElementById('search-name').addEventListener('focus', (e) => { activeInputField = e.target; showModalKeyboard(); });
        document.getElementById('search-total').addEventListener('focus', (e) => { activeInputField = e.target; showModalKeyboard(); });
        document.getElementById('search-single').addEventListener('focus', (e) => { activeInputField = e.target; showModalKeyboard(); });
        document.getElementById('search-origin').addEventListener('focus', (e) => { activeInputField = e.target; showModalKeyboard(); });
        document.getElementById('search-meanings').addEventListener('focus', (e) => { activeInputField = e.target; showModalKeyboard(); });

        // Numeric fields regex restrict listeners
        document.getElementById('formTotal').addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });
        document.getElementById('formSingle').addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });

        // Renders the standard alphabet grid on the main page (Urdu letter + value inside cards, NO numeric row)
        function renderMainGrid() {
            const grid = document.getElementById('lettersGrid');
            grid.innerHTML = '';

            let sortedData = [...letterData];
            sortedData.sort((a, b) => a.value - b.value);

            sortedData.forEach(item => {
                const card = document.createElement('div');
                card.className = 'letter-card';
                card.id = `card-${item.char}`;
                card.setAttribute('role', 'button');
                card.setAttribute('title', `Click to type ${item.char} (${item.name})`);
                card.innerHTML = `
                    <div class="letter-arabic">${item.char}</div>
                    <div class="letter-value">${item.value}</div>
                `;
                card.addEventListener('click', () => {
                    insertMainCharacter(item.char);
                });
                grid.appendChild(card);
            });
        }

        // Renders the Modal numeric row (1 to 0 keys)
        function renderModalNumericRow() {
            const row = document.getElementById('modalKeyboardNumericRow');
            row.innerHTML = '';
            const numbers = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'];
            numbers.forEach(num => {
                const key = document.createElement('button');
                key.className = 'btn';
                key.style.cssText = 'width: 30px; height: 30px; padding: 0; font-size: 0.95rem; display: flex; align-items: center; justify-content: center; background: rgba(255, 255, 255, 0.03); border-color: var(--card-border); color: var(--text-main); font-weight: bold; cursor: pointer; transition: all 0.2s;';
                key.textContent = num;
                key.addEventListener('click', () => {
                    insertModalCharacter(num);
                });
                row.appendChild(key);
            });
        }

        // Renders the Modal copy keyboard grid (Urdu letters ONLY, without values inside the boxes)
        function renderModalLettersGrid() {
            const grid = document.getElementById('modalLettersGrid');
            grid.innerHTML = '';

            let sortedData = [...letterData];
            sortedData.sort((a, b) => a.value - b.value);

            sortedData.forEach(item => {
                const card = document.createElement('div');
                card.className = 'letter-card';
                card.setAttribute('role', 'button');
                card.setAttribute('title', `Click to type ${item.char} (${item.name})`);
                card.innerHTML = `
                    <div class="letter-arabic">${item.char}</div>
                `;
                card.addEventListener('click', () => {
                    insertModalCharacter(item.char);
                });
                grid.appendChild(card);
            });
        }

        // Helper to insert character on the main page keyboard
        function insertMainCharacter(charToInsert) {
            const mainInputs = ['calcInput', 'originInput', 'meaningInput'];
            let inputField = activeInputField;
            if (!inputField || !mainInputs.includes(inputField.id)) {
                inputField = document.getElementById('calcInput');
            }
            const startPos = inputField.selectionStart;
            const endPos = inputField.selectionEnd;
            inputField.value = inputField.value.substring(0, startPos) + charToInsert + inputField.value.substring(endPos);
            inputField.selectionStart = inputField.selectionEnd = startPos + charToInsert.length;
            inputField.focus();
            if (inputField.id === 'calcInput') {
                calculateAbjad();
            }
        }

        // Helper to delete character on the main page keyboard
        function backspaceMainCharacter() {
            const mainInputs = ['calcInput', 'originInput', 'meaningInput'];
            let inputField = activeInputField;
            if (!inputField || !mainInputs.includes(inputField.id)) {
                inputField = document.getElementById('calcInput');
            }
            const startPos = inputField.selectionStart;
            const endPos = inputField.selectionEnd;
            if (startPos === endPos) {
                if (startPos > 0) {
                    inputField.value = inputField.value.substring(0, startPos - 1) + inputField.value.substring(endPos);
                    inputField.selectionStart = inputField.selectionEnd = startPos - 1;
                }
            } else {
                inputField.value = inputField.value.substring(0, startPos) + inputField.value.substring(endPos);
                inputField.selectionStart = inputField.selectionEnd = startPos;
            }
            inputField.focus();
            if (inputField.id === 'calcInput') {
                calculateAbjad();
            }
        }

        // Helper to insert character on the floating modal keyboard (restricts input to history form fields)
        function insertModalCharacter(charToInsert) {
            const allowedModalInputs = [
                'formName', 'formTotal', 'formSingle', 'formOrigin', 'formMeanings',
                'search-name', 'search-total', 'search-single', 'search-origin', 'search-meanings'
            ];
            let inputField = activeInputField;
            if (!inputField || !allowedModalInputs.includes(inputField.id)) {
                inputField = document.getElementById('formName');
            }
            const startPos = inputField.selectionStart;
            const endPos = inputField.selectionEnd;
            inputField.value = inputField.value.substring(0, startPos) + charToInsert + inputField.value.substring(endPos);
            
            // Re-focus and set correct selection bounds
            inputField.selectionStart = inputField.selectionEnd = startPos + charToInsert.length;
            inputField.focus();
            
            // If it is a search field, trigger table filter dynamically!
            if (inputField.id.startsWith('search-')) {
                const col = inputField.id.replace('search-', '');
                currentFilters[col] = inputField.value.trim();
                renderTable();
            }
        }

        // Helper to delete character on the floating modal keyboard (restricts input to history form fields)
        function backspaceModalCharacter() {
            const allowedModalInputs = [
                'formName', 'formTotal', 'formSingle', 'formOrigin', 'formMeanings',
                'search-name', 'search-total', 'search-single', 'search-origin', 'search-meanings'
            ];
            let inputField = activeInputField;
            if (!inputField || !allowedModalInputs.includes(inputField.id)) {
                inputField = document.getElementById('formName');
            }
            const startPos = inputField.selectionStart;
            const endPos = inputField.selectionEnd;
            if (startPos === endPos) {
                if (startPos > 0) {
                    inputField.value = inputField.value.substring(0, startPos - 1) + inputField.value.substring(endPos);
                    inputField.selectionStart = inputField.selectionEnd = startPos - 1;
                }
            } else {
                inputField.value = inputField.value.substring(0, startPos) + inputField.value.substring(endPos);
                inputField.selectionStart = inputField.selectionEnd = startPos;
            }
            inputField.focus();
            
            // If it is a search field, trigger table filter dynamically!
            if (inputField.id.startsWith('search-')) {
                const col = inputField.id.replace('search-', '');
                currentFilters[col] = inputField.value.trim();
                renderTable();
            }
        }

        // Active highlighted letters helper on main keyboard
        function clearHighlights() {
            document.querySelectorAll('#lettersGrid .letter-card').forEach(card => {
                card.classList.remove('highlighted');
            });
        }

        function highlightLetters(chars) {
            clearHighlights();
            chars.forEach(c => {
                const card = document.getElementById(`card-${c}`);
                if (card) {
                    card.classList.add('highlighted');
                }
            });
        }

        // Helper to calculate digital root (repeated sum of digits to get a single digit)
        function calculateDigitalRoot(value) {
            if (value <= 0) return 0;
            return 1 + ((value - 1) % 9);
        }

        // Calculation logic
        function calculateAbjad() {
            const inputField = document.getElementById('calcInput');
            
            let inputVal = inputField.value;
            inputVal = inputVal.replace(allowedUrduRegex, '');
            inputField.value = inputVal;

            const countHamza = true;
            const ignoreSpaces = true;
            
            let total = 0;
            const breakdown = [];
            const activeChars = new Set();

            for (let i = 0; i < inputVal.length; i++) {
                const char = inputVal[i];

                if (/\s/.test(char)) {
                    breakdown.push({ isSpacer: true });
                    continue;
                }

                let charVal = 0;
                let displayName = char;

                if (letterMap[char] !== undefined) {
                    charVal = letterMap[char];
                    activeChars.add(char);
                } else if (char === 'ء' || char === 'ئ' || char === 'ؤ') {
                    if (countHamza) {
                        charVal = 1;
                        displayName = char;
                    }
                } else if (char === 'ة') {
                    charVal = 5;
                }

                total += charVal;
                breakdown.push({ char: displayName, value: charVal });
            }

            // Set Total Value
            document.getElementById('totalValue').textContent = total;
            
            // Set Single Value (Digital Root)
            const singleVal = calculateDigitalRoot(total);
            document.getElementById('singleValue').textContent = singleVal;
            
            const breakdownFlow = document.getElementById('breakdownFlow');
            if (breakdown.length === 0) {
                breakdownFlow.innerHTML = '<span style="color: var(--text-muted); font-size: 0.8rem; font-style: italic;">Start typing or clicking letters to see breakdown...</span>';
                clearHighlights();
            } else {
                breakdownFlow.innerHTML = breakdown.map(item => {
                    if (item.isSpacer) {
                        return `<div class="breakdown-space-separator"></div>`;
                    }
                    return `
                        <div class="breakdown-item">
                            <span class="breakdown-letter">${item.char}</span>
                            <span class="breakdown-val">${item.value}</span>
                        </div>
                    `;
                }).join('');
                highlightLetters(activeChars);
            }
            if (typeof updateSuggestions === 'function') {
                updateSuggestions();
            }
        }

        // Action Handlers
        function clearInput() {
            const inputField = document.getElementById('calcInput');
            inputField.value = '';
            document.getElementById('originInput').value = '';
            document.getElementById('meaningInput').value = '';
            calculateAbjad();
            inputField.focus();
            activeInputField = inputField;
        }

        // SQLite Save functionality
        function saveCalculation() {
            const inputVal = document.getElementById('calcInput').value.trim();
            const totalVal = parseInt(document.getElementById('totalValue').textContent, 10);
            const singleVal = parseInt(document.getElementById('singleValue').textContent, 10);
            const originVal = document.getElementById('originInput').value.trim();
            const meaningVal = document.getElementById('meaningInput').value.trim();

            if (!inputVal || totalVal === 0) return;

            fetch('api.php?action=save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    name: inputVal,
                    total: totalVal,
                    single: singleVal,
                    origin: originVal,
                    meanings: meaningVal
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    clearInput();
                    loadHistory();
                } else {
                    console.error('Error saving calculation:', data.error);
                }
            })
            .catch(err => {
                console.error('Fetch error:', err);
            });
        }

        // SQLite Load history functionality (Populates modal table dynamically)
        function loadHistory() {
            fetch('api.php?action=history')
            .then(res => res.json())
            .then(data => {
                calculationsHistory = data || [];
                renderTable();
            })
            .catch(err => {
                console.error('Error loading history:', err);
            });
        }

        // Render & Update modal table based on search filters and sort settings
        function renderTable() {
            const tableBody = document.getElementById('historyTableBody');
            
            // Toggle Clear Filters button visibility
            const clearBtn = document.getElementById('btnClearFilters');
            if (clearBtn) {
                const filtersApplied = Object.values(currentFilters).some(val => val !== '');
                clearBtn.style.display = filtersApplied ? 'inline-block' : 'none';
            }
            
            // 1. Filter calculationsHistory
            let processedData = calculationsHistory.filter(item => {
                const nameMatch = item.name.toLowerCase().includes(currentFilters.name.toLowerCase());
                const totalMatch = String(item.total).includes(currentFilters.total);
                const singleMatch = String(item.single).includes(currentFilters.single);
                const originMatch = (item.origin || '').toLowerCase().includes(currentFilters.origin.toLowerCase());
                const meaningsMatch = (item.meanings || '').toLowerCase().includes(currentFilters.meanings.toLowerCase());
                return nameMatch && totalMatch && singleMatch && originMatch && meaningsMatch;
            });

            // 2. Sort processedData
            if (currentSortCol) {
                processedData.sort((a, b) => {
                    let valA = a[currentSortCol];
                    let valB = b[currentSortCol];

                    if (typeof valA === 'string') {
                        valA = valA.toLowerCase();
                        valB = valB.toLowerCase();
                    }
                    
                    if (valA < valB) return currentSortDir === 'asc' ? -1 : 1;
                    if (valA > valB) return currentSortDir === 'asc' ? 1 : -1;
                    return 0;
                });
            }

            // 3. Render HTML
            if (processedData.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--text-muted); font-style: italic;">No matching records found</td></tr>';
                return;
            }

             tableBody.innerHTML = processedData.map(item => `
                <tr>
                    <td class="arabic-cell">${item.name}</td>
                    <td>${item.total}</td>
                    <td>${item.single}</td>
                    <td>${item.origin || '-'}</td>
                    <td>${item.meanings || '-'}</td>
                    <td style="text-align: center; display: flex; gap: 0.35rem; justify-content: center;">
                        <button class="btn btn-primary" onclick="startEditRecord(${item.id})" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; border-radius: 6px; cursor: pointer;">Edit</button>
                        <button class="btn btn-danger" onclick="deleteRecord(${item.id})" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; border-radius: 6px; cursor: pointer;">Delete</button>
                    </td>
                </tr>
            `).join('');

            // 4. Update Sort UI Icons
            const cols = ['name', 'total', 'single', 'origin', 'meanings'];
            cols.forEach(col => {
                const icon = document.getElementById(`sort-icon-${col}`);
                if (currentSortCol === col) {
                    icon.textContent = currentSortDir === 'asc' ? '▲' : '▼';
                    icon.style.color = 'var(--gold-accent)';
                } else {
                    icon.textContent = '⇅';
                    icon.style.color = 'var(--text-muted)';
                }
            });
        }

        // Add/Edit Form Controls
        const addEditRecordForm = document.getElementById('addEditRecordForm');
        const formTitle = document.getElementById('formTitle');
        const editRecordId = document.getElementById('editRecordId');
        const formName = document.getElementById('formName');
        const formTotal = document.getElementById('formTotal');
        const formSingle = document.getElementById('formSingle');
        const formOrigin = document.getElementById('formOrigin');
        const formMeanings = document.getElementById('formMeanings');

        // Show form for new record
        document.getElementById('btnAddNew').addEventListener('click', () => {
            formTitle.textContent = 'Add New Record';
            editRecordId.value = '';
            formName.value = '';
            formTotal.value = '';
            formSingle.value = '';
            formOrigin.value = '';
            formMeanings.value = '';
            addEditRecordForm.style.display = 'block';
            formName.focus();
            activeInputField = formName;
            showModalKeyboard(); // Automatically popup keyboard
        });

        // Start editing existing record
        function startEditRecord(id) {
            const item = calculationsHistory.find(x => x.id === id);
            if (!item) return;
            formTitle.textContent = 'Edit Record';
            editRecordId.value = item.id;
            formName.value = item.name;
            formTotal.value = item.total;
            formSingle.value = item.single;
            formOrigin.value = item.origin || '';
            formMeanings.value = item.meanings || '';
            addEditRecordForm.style.display = 'block';
            formName.focus();
            activeInputField = formName;
            showModalKeyboard(); // Automatically popup keyboard
        }

        // Delete calculation record with confirmation
        function deleteRecord(id) {
            const item = calculationsHistory.find(x => x.id === id);
            if (!item) return;
            if (confirm(`Are you sure you want to delete the record for "${item.name}"?`)) {
                fetch('api.php?action=delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: id })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        loadHistory();
                    } else {
                        alert('Error deleting record: ' + (data.error || 'unknown error'));
                    }
                })
                .catch(err => {
                    console.error('Delete error:', err);
                });
            }
        }

        // Cancel Form Action
        document.getElementById('btnCancelForm').addEventListener('click', () => {
            addEditRecordForm.style.display = 'none';
        });

        // Submit Form (handles both Add and Edit via SQLite endpoints)
        document.getElementById('btnSubmitForm').addEventListener('click', () => {
            const nameVal = formName.value.trim();
            const totalVal = parseInt(formTotal.value, 10);
            const singleVal = parseInt(formSingle.value, 10);
            const originVal = formOrigin.value.trim();
            const meaningsVal = formMeanings.value.trim();
            const idVal = editRecordId.value;

            if (!nameVal || isNaN(totalVal) || isNaN(singleVal)) {
                alert('Please fill out Name, Total, and Single fields.');
                return;
            }

            const endpoint = idVal ? 'api.php?action=edit' : 'api.php?action=save';
            const bodyObj = {
                name: nameVal,
                total: totalVal,
                single: singleVal,
                origin: originVal,
                meanings: meaningsVal
            };
            if (idVal) bodyObj.id = parseInt(idVal, 10);

            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(bodyObj)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    addEditRecordForm.style.display = 'none';
                    loadHistory();
                } else {
                    alert('Error saving record: ' + (data.error || 'unknown error'));
                }
            })
            .catch(err => {
                console.error('Submit error:', err);
            });
        });

        // Header click handler to trigger sorting
        document.querySelectorAll('#sortHeaderRow th.sortable').forEach(header => {
            header.addEventListener('click', () => {
                const col = header.getAttribute('data-col');
                if (currentSortCol === col) {
                    currentSortDir = currentSortDir === 'asc' ? 'desc' : 'asc';
                } else {
                    currentSortCol = col;
                    currentSortDir = 'asc';
                }
                renderTable();
            });
        });

        // Search input keyup/change handlers
        const searchInputs = ['name', 'total', 'single', 'origin', 'meanings'];
        searchInputs.forEach(col => {
            const input = document.getElementById(`search-${col}`);
            input.addEventListener('input', (e) => {
                currentFilters[col] = e.target.value.trim();
                renderTable();
            });
        });

        // Clear filters event listener
        document.getElementById('btnClearFilters').addEventListener('click', () => {
            searchInputs.forEach(col => {
                currentFilters[col] = '';
                const input = document.getElementById(`search-${col}`);
                if (input) {
                    input.value = '';
                }
            });
            renderTable();
        });

        // Modal Open / Close Event Listeners
        const memoModal = document.getElementById('memoModal');
        document.getElementById('btnMemo').addEventListener('click', () => {
            loadHistory();
            memoModal.style.display = 'flex';
        });

        document.getElementById('btnCloseModal').addEventListener('click', () => {
            memoModal.style.display = 'none';
        });

        // Close modal when clicking outside the content area
        window.addEventListener('click', (e) => {
            if (e.target === memoModal) {
                memoModal.style.display = 'none';
            }
        });

        // Keyboard Show/Hide toggler inside the Modal Header (for the copy keyboard)
        const btnToggleKeyboard = document.getElementById('btnToggleKeyboard');
        const modalKeyboardContainer = document.getElementById('modalKeyboardContainer');
        btnToggleKeyboard.addEventListener('click', () => {
            if (modalKeyboardContainer.style.display === 'none') {
                modalKeyboardContainer.style.top = "";
                modalKeyboardContainer.style.left = "50%";
                modalKeyboardContainer.style.transform = "translateX(-50%)";
                modalKeyboardContainer.style.display = 'flex';
                btnToggleKeyboard.textContent = 'Hide Keyboard';
            } else {
                modalKeyboardContainer.style.display = 'none';
                btnToggleKeyboard.textContent = 'Show Keyboard';
            }
        });

        // Main keyboard triggers
        document.getElementById('btnSpaceBar').addEventListener('click', () => { insertMainCharacter(' '); });
        document.getElementById('btnBackspace').addEventListener('click', backspaceMainCharacter);

        // Modal copy keyboard triggers (using dedicated modal handlers)
        document.getElementById('modalBtnSpaceBar').addEventListener('click', () => { insertModalCharacter(' '); });
        document.getElementById('modalBtnBackspace').addEventListener('click', backspaceModalCharacter);

        // Event Listeners & Suggestions Logic
        const calcInput = document.getElementById('calcInput');
        const suggestionsBox = document.getElementById('suggestionsBox');

        calcInput.addEventListener('input', () => {
            calculateAbjad();
        });

        // Hide suggestions when clicking outside
        document.addEventListener('click', (e) => {
            if (e.target !== calcInput && e.target !== suggestionsBox) {
                suggestionsBox.style.display = 'none';
            }
        });

        function updateSuggestions() {
            const cInput = document.getElementById('calcInput');
            const sBox = document.getElementById('suggestionsBox');
            if (!cInput || !sBox) return;

            const query = cInput.value.trim();
            if (!query) {
                sBox.style.display = 'none';
                return;
            }

            // Filter history for matches in name, origin, or meanings
            const matches = calculationsHistory.filter(item => 
                item.name.includes(query) || 
                (item.origin && item.origin.toLowerCase().includes(query.toLowerCase())) ||
                (item.meanings && item.meanings.toLowerCase().includes(query.toLowerCase()))
            );

            if (matches.length === 0) {
                sBox.style.display = 'none';
                return;
            }

            sBox.innerHTML = matches.slice(0, 5).map(item => `
                <div class="suggestion-item" onclick="selectSuggestion('${item.name.replace(/'/g, "\\'")}', '${(item.origin || '').replace(/'/g, "\\'")}', '${(item.meanings || '').replace(/'/g, "\\'")}')">
                    <span class="suggestion-name">${item.name}</span>
                    <span class="suggestion-details">
                        <span>${item.origin || ''}</span>
                        <span class="suggestion-val">Abjad: ${item.total}</span>
                    </span>
                </div>
            `).join('');

            sBox.style.display = 'block';
        }

        window.selectSuggestion = function(name, origin, meanings) {
            const cInput = document.getElementById('calcInput');
            const sBox = document.getElementById('suggestionsBox');
            if (cInput) cInput.value = name;
            document.getElementById('originInput').value = origin;
            document.getElementById('meaningInput').value = meanings;
            calculateAbjad();
            if (sBox) sBox.style.display = 'none';
        };

        document.getElementById('btnClear').addEventListener('click', () => { clearInput(); const sBox = document.getElementById('suggestionsBox'); if (sBox) sBox.style.display = 'none'; });
        document.getElementById('btnSave').addEventListener('click', () => { saveCalculation(); const sBox = document.getElementById('suggestionsBox'); if (sBox) sBox.style.display = 'none'; });

        // Initial Renders
        renderMainGrid(); // Main grid stays visible and holds letter values
        renderModalNumericRow(); // Modal keyboard Top row is numeric
        renderModalLettersGrid(); // Modal copy grid has alphabets only
        loadHistory();

        // Make modal keyboard moveable
        makeElementDraggable(modalKeyboardContainer);

        // Dark/Soft Mode Theme Toggle logic
        const btnThemeToggle = document.getElementById('btnThemeToggle');
        const themeToggleIcon = document.getElementById('themeToggleIcon');

        // Check if theme was saved previously
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'soft') {
            document.body.classList.add('soft-mode');
            themeToggleIcon.textContent = '☀️';
        } else {
            themeToggleIcon.textContent = '🌗';
        }

        btnThemeToggle.addEventListener('click', () => {
            document.body.classList.toggle('soft-mode');
            if (document.body.classList.contains('soft-mode')) {
                localStorage.setItem('theme', 'soft');
                themeToggleIcon.textContent = '☀️';
            } else {
                localStorage.setItem('theme', 'dark');
                themeToggleIcon.textContent = '🌗';
            }
        });
    </script>
</body>
</html>
