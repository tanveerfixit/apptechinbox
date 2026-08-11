<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
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
            --bg-color: #000000;
            --card-bg: #1c1c1e;
            --card-border: rgba(255, 255, 255, 0.1);
            --primary-glow: rgba(0, 122, 255, 0.12);
            --accent-color: #007AFF;
            --accent-glow: #0056CC;
            --text-main: #f5f5f7;
            --text-muted: #86868b;
            --gold-accent: #FF9F0A;
            --gold-glow: rgba(255, 159, 10, 0.15);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', 'Outfit', sans-serif;
            min-height: 100vh;
            padding: 2rem 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        header {
            text-align: center;
            margin-bottom: 0.75rem;
            max-width: 800px;
        }

        h1 {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-main);
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
            border-radius: 12px;
            padding: 1.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            position: relative;
            overflow: hidden;
            width: 100%;
            max-width: 756px;
            margin: 0 auto;
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
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid var(--card-border);
            border-radius: 8px;
            color: var(--text-main);
            font-family: 'Amiri', serif;
            font-size: 1.4rem;
            direction: rtl;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .calc-input:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px var(--primary-glow);
        }

        .calc-input::placeholder {
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', sans-serif;
            font-size: 1rem;
            direction: ltr;
            text-align: left;
            color: var(--text-muted);
            opacity: 0.5;
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
            background: rgba(255, 255, 255, 0.04);
            padding: 1.25rem;
            border-radius: 10px;
            border: 1px solid var(--card-border);
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
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--card-border);
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

        /* Elemental Temperament Layout */
        .elements-line-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.5rem;
            width: 100%;
            margin-top: -0.2rem;
            margin-bottom: 0.5rem;
            padding: 0 0.1rem;
            direction: rtl;
        }

        .element-item {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
            min-width: 0;
        }

        .element-header {
            display: flex;
            justify-content: space-between;
            font-size: 0.72rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .element-bar-track {
            width: 100%;
            height: 4px;
            background: rgba(120, 120, 120, 0.2);
            border-radius: 2px;
            overflow: hidden;
        }

        /* Action Row & Detail Input Layout */
        .action-fields-row {
            display: flex;
            gap: 0.4rem;
            width: 100%;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .action-buttons-group {
            display: flex;
            gap: 0.4rem;
            align-items: center;
        }

        .origin-input-wrapper {
            width: 90px;
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
            flex-shrink: 0;
        }

        .meaning-input-wrapper {
            flex: 1;
            min-width: 120px;
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }

        /* Buttons */
        .btn {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid var(--card-border);
            color: var(--text-main);
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            font-size: 0.9rem;
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', sans-serif;
            transition: background 0.15s ease, border-color 0.15s ease;
        }

        .btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .btn-primary {
            background: var(--accent-color);
            border-color: var(--accent-color);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--accent-glow);
            border-color: var(--accent-glow);
        }

        .btn-danger {
            background: rgba(255, 59, 48, 0.1);
            border-color: rgba(255, 59, 48, 0.2);
            color: #FF453A;
        }

        .btn-danger:hover {
            background: rgba(255, 59, 48, 0.18);
            border-color: rgba(255, 59, 48, 0.35);
        }

        /* Full Screen Saved / Memo Page View */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100vw;
            height: 100vh;
            background: var(--bg-color);
            display: none;
            flex-direction: column;
            z-index: 10000;
            overflow-y: auto;
            padding: 0;
        }

        .modal-content {
            background: var(--bg-color);
            border: none;
            border-radius: 0;
            width: 100vw;
            height: 100vh;
            max-width: 100vw;
            max-height: 100vh;
            padding: 1.25rem 1.5rem;
            box-shadow: none;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            position: relative;
        }

        .modal-header {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
            width: 100%;
            border-bottom: 1px solid var(--card-border);
            padding-bottom: 0.75rem;
        }

        .modal-header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            gap: 0.5rem;
        }

        .modal-title-wrapper {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            flex: 1;
            justify-content: center;
        }

        .modal-title-icon {
            font-size: 1.2rem;
        }

        .modal-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-main);
            white-space: nowrap;
        }

        .modal-header-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            justify-content: flex-end;
        }

        .modal-close {
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 1.6rem;
            cursor: pointer;
            transition: color 0.2s;
            line-height: 1;
            padding: 0 0.25rem;
        }

        .modal-close:hover {
            color: #FF453A;
        }

        /* History Table Styling: Configured for RTL layout & Grid lines */
        .history-table-wrapper {
            overflow-x: auto;
            flex: 1;
            min-height: 250px;
            max-height: calc(100vh - 220px);
            border-radius: 10px;
            border: 1px solid var(--card-border);
            background: var(--card-bg);
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            direction: rtl; /* Right-to-Left arrangement */
            text-align: right;
            font-size: 0.9rem;
        }

        .history-table th {
            background: rgba(255, 255, 255, 0.04);
            color: var(--text-main);
            font-weight: 600;
            padding: 0.75rem 1rem;
            border: 1px solid var(--card-border);
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
            background: rgba(0, 122, 255, 0.08);
            color: var(--text-main);
            cursor: pointer;
        }

        .history-table td {
            padding: 0.75rem 1rem;
            border: 1px solid var(--card-border);
            color: var(--text-main);
            vertical-align: middle;
        }

        /* Alternate color rows (zebra grid) */
        .history-table tbody tr:nth-child(even) {
            background: rgba(255, 255, 255, 0.02);
        }
        .history-table tbody tr:nth-child(odd) {
            background: transparent;
        }

        .history-table tr:hover {
            background: rgba(0, 122, 255, 0.06);
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
            background: rgba(255, 255, 255, 0.06);
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
            background: rgba(255, 255, 255, 0.08);
        }

        /* Floating, Draggable style for modal keyboard copy */
        .floating-keyboard {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            width: auto;
            max-width: 440px;
            background: var(--card-bg) !important;
            border: 1px solid var(--card-border) !important;
            border-radius: 12px !important;
            padding: 1rem !important;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4) !important;
            z-index: 1010;
            cursor: move;
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
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--card-border);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            transition: background 0.15s ease, border-color 0.15s ease;
            cursor: pointer;
            user-select: none;
            width: 71px;
            height: 71px;
            aspect-ratio: 1 / 1;
            overflow: hidden;
        }

        .letter-card:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .letter-card:active {
            transform: scale(0.97);
        }

        .letter-card.highlighted {
            background: rgba(0, 122, 255, 0.12);
            border-color: var(--accent-color);
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
                padding: 0.5rem 0.25rem;
            }
            header {
                margin-bottom: 0.4rem;
            }
            h1 {
                font-size: 1.15rem;
            }
            .container {
                gap: 0.75rem;
            }
            .calculator-card {
                padding: 0.6rem;
                border-radius: 10px;
                gap: 0.75rem;
            }
            .calc-input {
                padding: 0.6rem 0.75rem;
                font-size: 1.25rem;
                border-radius: 8px;
            }
            .calc-input::placeholder {
                font-size: 0.8rem;
            }
            /* Elemental bars - tighter on tablets */
            .elements-line-container {
                gap: 0.5rem !important;
                margin-bottom: 0.5rem !important;
                margin-top: 0 !important;
            }
            .calc-results {
                flex-direction: column;
                align-items: stretch;
                padding: 0.6rem;
                gap: 0.75rem;
                border-radius: 10px;
            }
            .values-wrapper {
                justify-content: center;
                margin-bottom: 0.25rem;
                gap: 1.25rem;
            }
            .total-value {
                font-size: 1.6rem;
            }
            .breakdown-container {
                min-width: 0;
            }
            .breakdown-item {
                padding: 0.25rem 0.35rem;
                min-width: 32px;
            }
            .breakdown-letter {
                font-size: 0.95rem;
            }
            .breakdown-val {
                font-size: 0.6rem;
            }
            .letters-grid {
                max-width: 100%;
                gap: 0.4rem;
            }
            .letter-card {
                width: 55px;
                height: 55px;
            }
            .modal-overlay {
                padding: 0 !important;
                background: var(--bg-color) !important;
                backdrop-filter: none !important;
            }
            .modal-content {
                padding: 0.75rem 0.5rem !important;
                border-radius: 0 !important;
                border: none !important;
                width: 100% !important;
                max-width: 100% !important;
                height: 100vh !important;
                max-height: 100vh !important;
                box-shadow: none !important;
                background: var(--bg-color) !important;
                gap: 0.75rem !important;
                display: flex;
                flex-direction: column;
            }
            .modal-header {
                padding-bottom: 0.4rem !important;
                border-bottom-color: var(--card-border) !important;
                gap: 0.4rem !important;
            }
            .modal-title {
                font-size: 0.95rem !important;
            }
            .modal-title-icon {
                font-size: 1rem !important;
            }
            .modal-header-actions {
                width: 100% !important;
                justify-content: stretch !important;
                gap: 0.4rem !important;
            }
            .modal-header-actions .btn {
                flex: 1 !important;
                text-align: center !important;
                font-size: 0.78rem !important;
                padding: 0.4rem 0.5rem !important;
            }
            .history-table-wrapper {
                flex: 1 !important;
                max-height: calc(100vh - 120px) !important;
                border-radius: 8px !important;
                background: var(--card-bg) !important;
            }
            .history-table th, .history-table td {
                padding: 0.4rem 0.35rem !important;
                font-size: 0.8rem !important;
            }
            .history-table td.arabic-cell {
                font-size: 1.05rem !important;
            }
            .table-search-input {
                padding: 0.25rem 0.35rem !important;
                font-size: 0.75rem !important;
            }
            /* Form inside modal responsiveness */
            #addEditRecordForm {
                padding: 0.6rem !important;
                margin-bottom: 0.5rem !important;
                border-radius: 8px !important;
                background: var(--card-bg) !important;
            }
            #addEditRecordForm h3 {
                font-size: 0.9rem !important;
                margin-bottom: 0.5rem !important;
            }
            #addEditRecordForm > div:first-of-type {
                display: grid !important;
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 0.4rem !important;
            }
            #addEditRecordForm > div:first-of-type > div {
                width: 100% !important;
                min-width: 0 !important;
                flex: none !important;
            }
            #addEditRecordForm > div:first-of-type > div:first-child {
                grid-column: span 2 !important; /* Name field spans full width */
            }
            #addEditRecordForm > div:first-of-type > div:last-child {
                grid-column: span 2 !important; /* Meanings field spans full width */
            }
            #addEditRecordForm input {
                height: 32px !important;
                font-size: 0.85rem !important;
                padding: 0.3rem 0.4rem !important;
            }
            .history-table {
                min-width: 580px; /* Ensure table scrolls smoothly without squishing */
            }
            .floating-keyboard {
                width: 92%;
                bottom: 1rem;
                max-width: 360px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 0.15rem 0;
            }
            header {
                margin-bottom: 0.25rem;
            }
            h1 {
                font-size: 1rem;
            }
            .container {
                gap: 0.5rem;
            }
            .calculator-card {
                padding: 0.4rem;
                border-radius: 6px;
                gap: 0.5rem;
            }
            .calc-input {
                padding: 0.5rem 0.6rem;
                font-size: 1.15rem;
                border-radius: 6px;
            }
            .calc-input::placeholder {
                font-size: 0.7rem;
            }
            /* Elemental bars - compact for phones */
            .elements-line-container {
                gap: 0.35rem !important;
                padding: 0 !important;
                margin-top: 0 !important;
                margin-bottom: 0.35rem !important;
            }
            .elements-line-container > div {
                min-width: 60px !important;
            }
            .elements-line-container span {
                font-size: 0.62rem !important;
            }
            .calc-results {
                padding: 0.5rem;
                gap: 0.5rem;
                border-radius: 8px;
            }
            .values-wrapper {
                gap: 0.75rem;
            }
            .total-value {
                font-size: 1.35rem;
            }
            .total-value-label {
                font-size: 0.7rem;
            }
            .breakdown-item {
                padding: 0.2rem 0.3rem;
                min-width: 28px;
                border-radius: 4px;
            }
            .breakdown-letter {
                font-size: 0.85rem;
            }
            .breakdown-val {
                font-size: 0.55rem;
            }
            .breakdown-flow {
                gap: 0.25rem;
            }
            .letters-grid {
                gap: 0.3rem;
            }
            .letter-card {
                width: 45px;
                height: 45px;
                border-radius: 6px;
            }
            .letter-arabic {
                font-size: 1.3rem;
                top: 6px;
                right: 5px;
            }
            .letter-value {
                font-size: 0.6rem;
                bottom: 2px;
                left: 3px;
            }
            .btn {
                padding: 0.4rem 0.6rem;
                font-size: 0.75rem;
                border-radius: 6px;
            }
            .floating-keyboard .letter-card {
                width: 34px !important;
                height: 34px !important;
            }
            .floating-keyboard .letter-card .letter-arabic {
                font-size: 1.2rem !important;
            }
        }

        /* Smooth transitions for theme toggle */
        body, .calculator-card, .calc-input, .btn, .letter-card, .modal-content, .history-table th, .history-table td, .history-table-wrapper, .table-search-input {
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease, box-shadow 0.3s ease, background-image 0.3s ease;
        }

        /* Soft Mode Styles */
        body.soft-mode {
            --bg-color: #f5f5f7;
            --card-bg: #ffffff;
            --card-border: rgba(0, 0, 0, 0.08);
            --primary-glow: rgba(0, 122, 255, 0.1);
            --accent-color: #007AFF;
            --accent-glow: #0056CC;
            --text-main: #1d1d1f;
            --text-muted: #86868b;
            --gold-accent: #c93400;
            --gold-glow: rgba(201, 52, 0, 0.1);
        }

        body.soft-mode h1 {
            color: #1d1d1f;
        }

        body.soft-mode .calc-input {
            background: #ffffff;
            color: #1d1d1f;
            border-color: rgba(0, 0, 0, 0.12);
        }

        body.soft-mode .calc-input::placeholder {
            color: #86868b;
            opacity: 0.6;
        }

        body.soft-mode .calc-results {
            background: rgba(0, 0, 0, 0.02);
            border-color: rgba(0, 0, 0, 0.06);
        }

        body.soft-mode .letter-arabic {
            color: #1d1d1f;
        }

        body.soft-mode .letter-card {
            background: rgba(0, 0, 0, 0.03);
        }

        body.soft-mode .letter-card:hover {
            background: rgba(0, 0, 0, 0.06);
            border-color: rgba(0, 0, 0, 0.15);
        }

        body.soft-mode .modal-content {
            background: var(--bg-color);
            border-color: rgba(0, 0, 0, 0.08);
        }

        body.soft-mode .modal-title {
            color: #1d1d1f;
        }

        body.soft-mode .history-table-wrapper {
            background: #ffffff;
        }

        body.soft-mode .history-table th {
            background: var(--bg-color);
            color: #1d1d1f;
            border-color: rgba(0, 0, 0, 0.08);
        }

        body.soft-mode .history-table td {
            color: #1d1d1f;
            border-color: rgba(0, 0, 0, 0.06);
        }

        body.soft-mode .history-table tbody tr:nth-child(even) {
            background: rgba(0, 0, 0, 0.015);
        }
        body.soft-mode .history-table tbody tr:nth-child(odd) {
            background: transparent;
        }

        body.soft-mode .history-table tr:hover {
            background: rgba(0, 122, 255, 0.04);
        }

        body.soft-mode .table-search-input {
            background: #ffffff;
            color: #1d1d1f;
            border-color: rgba(0, 0, 0, 0.1);
        }

        body.soft-mode .table-search-input:focus {
            background: #fff;
            border-color: var(--accent-color);
        }

        body.soft-mode .floating-keyboard {
            background: #ffffff !important;
            border: 1px solid rgba(0, 0, 0, 0.1) !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08) !important;
        }

        body.soft-mode #addEditRecordForm > div {
            background: #ffffff;
            border-color: rgba(0, 0, 0, 0.1);
        }

        body.soft-mode #addEditRecordForm label {
            color: #1d1d1f !important;
            font-weight: 600 !important;
        }

        body.soft-mode #formTitle {
            color: #1d1d1f;
        }

        body.soft-mode .btn-primary {
            color: #ffffff;
        }

        body.soft-mode .btn:not(.btn-primary):not(.btn-danger) {
            color: #1d1d1f;
            background: rgba(0, 0, 0, 0.04);
            border-color: rgba(0, 0, 0, 0.12);
        }

        body.soft-mode .btn:not(.btn-primary):not(.btn-danger):hover {
            background: rgba(0, 0, 0, 0.07);
        }

        body.soft-mode .btn-danger {
            background: rgba(255, 59, 48, 0.06);
            border-color: rgba(255, 59, 48, 0.15);
            color: #FF3B30;
        }

        body.soft-mode .btn-danger:hover {
            background: rgba(255, 59, 48, 0.12);
            border-color: rgba(255, 59, 48, 0.25);
        }

        body.soft-mode #btnBackspace, body.soft-mode #modalBtnBackspace {
            color: #FF3B30;
            border-color: rgba(255, 59, 48, 0.15);
            background: rgba(255, 59, 48, 0.06);
        }

        /* Suggestions Dropdown Style */
        .suggestions-box {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 10px;
            margin-top: 0.35rem;
            max-height: 200px;
            overflow-y: auto;
            z-index: 100;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
            direction: rtl;
        }

        .suggestion-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--card-border);
            transition: background 0.15s ease;
        }

        .suggestion-item:last-child {
            border-bottom: none;
        }

        .suggestion-item:hover {
            background: rgba(0, 122, 255, 0.08);
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
            background: #ffffff;
            border-color: rgba(0, 0, 0, 0.1);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }
        body.soft-mode .suggestion-item {
            border-bottom-color: rgba(0, 0, 0, 0.05);
        }
        body.soft-mode .suggestion-item:hover {
            background: rgba(0, 122, 255, 0.05);
        }
        body.soft-mode .suggestion-name {
            color: #1d1d1f;
        }
    </style>
</head>
<body>

    <header style="display: flex; align-items: center; justify-content: center; gap: 0.75rem; position: relative;">
        <h1 style="margin-bottom: 0;">Huroof-e-Abjad Computation</h1>
        <button id="btnThemeToggle" class="btn" aria-label="Toggle Theme" style="padding: 0; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--card-border); cursor: pointer;">
            <span id="themeToggleIcon" style="font-size: 1.1rem; line-height: 1;">🌗</span>
        </button>
    </header>

    <main class="container">
        
        <!-- Interactive Calculator -->
        <div class="calculator-card">
            
            <!-- Main Name Field -->
            <div class="input-group" style="position: relative; margin-bottom: 0.5rem;">
                <div class="input-wrapper">
                    <input type="text" id="calcInput" class="calc-input" placeholder="Type Urdu text here or click reference grid boxes below to input..." autocomplete="off">
                </div>
                <div id="suggestionsBox" class="suggestions-box" style="display: none;"></div>
            </div>

            <!-- Elemental Temperament (عناصر کی کیفیت) - Fluid 4-Column Grid -->
            <div class="elements-line-container">
                <!-- Fire Element -->
                <div class="element-item">
                    <div class="element-header" style="color: #ef4444;">
                        <span>🔥 Fire (آتشی)</span>
                        <span id="val-fire">0%</span>
                    </div>
                    <div class="element-bar-track">
                        <div id="bar-fire" style="width: 0%; height: 100%; background: #ef4444; transition: width 0.3s ease;"></div>
                    </div>
                </div>
                <!-- Air Element -->
                <div class="element-item">
                    <div class="element-header" style="color: #f59e0b;">
                        <span>💨 Air (بادی)</span>
                        <span id="val-air">0%</span>
                    </div>
                    <div class="element-bar-track">
                        <div id="bar-air" style="width: 0%; height: 100%; background: #f59e0b; transition: width 0.3s ease;"></div>
                    </div>
                </div>
                <!-- Water Element -->
                <div class="element-item">
                    <div class="element-header" style="color: #38bdf8;">
                        <span>💧 Water (آبی)</span>
                        <span id="val-water">0%</span>
                    </div>
                    <div class="element-bar-track">
                        <div id="bar-water" style="width: 0%; height: 100%; background: #38bdf8; transition: width 0.3s ease;"></div>
                    </div>
                </div>
                <!-- Earth Element -->
                <div class="element-item">
                    <div class="element-header" style="color: #10b981;">
                        <span>🪨 Earth (خاکی)</span>
                        <span id="val-earth">0%</span>
                    </div>
                    <div class="element-bar-track">
                        <div id="bar-earth" style="width: 0%; height: 100%; background: #10b981; transition: width 0.3s ease;"></div>
                    </div>
                </div>
            </div>

            <!-- Optional Fields Row: Buttons, Origin and Meanings -->
            <div class="action-fields-row">
                <div class="action-buttons-group">
                    <button id="btnClear" class="btn btn-primary" style="padding: 0.5rem 0.65rem; margin: 0; font-size: 0.8rem; height: 34px; border-radius: 8px;">Clear</button>
                    <button id="btnSave" class="btn btn-primary" style="padding: 0.5rem 0.65rem; margin: 0; font-size: 0.8rem; height: 34px; border-radius: 8px;">Save</button>
                    <button id="btnMemo" class="btn btn-primary" style="padding: 0.5rem 0.65rem; margin: 0; font-size: 0.8rem; height: 34px; border-radius: 8px;">Memo</button>
                </div>
                
                <div class="origin-input-wrapper">
                    <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 500;">Origin</label>
                    <input type="text" id="originInput" class="calc-input detail-input" placeholder="Origin..." style="padding: 0.4rem 0.5rem; font-size: 0.85rem; border-radius: 8px; height: 34px;">
                </div>
                
                <div class="meaning-input-wrapper">
                    <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 500;">Meanings (Optional)</label>
                    <input type="text" id="meaningInput" class="calc-input detail-input" placeholder="e.g. Gracious, King..." style="padding: 0.4rem 0.5rem; font-size: 0.85rem; border-radius: 8px; height: 34px;">
                </div>
            </div>

            <div class="calc-results">
                <!-- Swapped order so Single is on the left and Total is on the right -->
                <div class="values-wrapper">
                    <div class="total-value-container">
                        <div class="total-value-label">Single</div>
                        <div class="total-value" id="singleValue" style="color: var(--accent-color);">0</div>
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

        <!-- Main Page Keyboard -->
        <div class="chart-section" id="keyboardContainer">
            <div class="section-header" style="flex-direction: column; align-items: stretch; gap: 0.5rem;">
                <!-- Header with Hide/Show Keyboard Toggle Button -->
                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; max-width: 640px; margin: 0 auto; padding: 0 0.1rem;">
                    <span style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted);">Urdu Keyboard ⌨️</span>
                    <button id="btnToggleMainKeyboard" class="btn" style="font-size: 0.75rem; padding: 0.25rem 0.6rem; border-color: var(--accent-color); color: var(--text-main);">Hide Keyboard ⌨️</button>
                </div>
                <!-- Action Row (Space bar & Backspace) -->
                <div id="keyboardActionRow" style="display: flex; gap: 0.6rem; width: 100%; max-width: 640px; margin: 0 auto;">
                    <button id="btnSpaceBar" class="btn btn-primary" style="flex: 1; margin: 0; padding: 0.75rem 0; text-align: center;">Space Bar ␣</button>
                    <button id="btnBackspace" class="btn" style="flex: 1; margin: 0; padding: 0.75rem 0; text-align: center; background: rgba(255, 59, 48, 0.1); border-color: rgba(255, 59, 48, 0.2); color: #FF453A;">Backspace ⌫</button>
                </div>
            </div>
            
            <div class="letters-grid" id="lettersGrid">
                <!-- Cards will be dynamically injected by JS -->
            </div>
        </div>

    </div>

    <!-- Full Screen Saved History & Memo Page -->
    <div id="memoModal" class="modal-overlay">
        <div class="modal-content">
            <!-- Full Screen Responsive Header Bar -->
            <div class="modal-header">
                <div class="modal-header-top">
                    <button class="btn btn-primary" id="btnBackToCalc" style="font-size: 0.8rem; padding: 0.35rem 0.65rem; display: flex; align-items: center; gap: 0.25rem;">
                        ← Back
                    </button>
                    <div class="modal-title-wrapper">
                        <span class="modal-title-icon">💾</span>
                        <span class="modal-title">Saved History & Memos</span>
                    </div>
                    <button class="modal-close" id="btnCloseModal" title="Close">&times;</button>
                </div>
                
                <div class="modal-header-actions">
                    <button id="btnAddNew" class="btn btn-primary" style="font-size: 0.8rem; padding: 0.35rem 0.65rem;">+ Add New</button>
                    <button id="btnToggleKeyboard" class="btn" style="font-size: 0.8rem; padding: 0.35rem 0.65rem; border-color: var(--accent-color); color: var(--text-main);">Show Keyboard</button>
                </div>
            </div>
            
            <!-- Clean Popup Modal for Add / Update Record -->
            <div id="addEditRecordForm" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 20000; align-items: center; justify-content: center; padding: 1rem;">
                <div style="background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 12px; width: 100%; max-width: 520px; box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3); overflow: hidden; animation: popIn 0.2s ease-out;">
                    <!-- Header -->
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.25rem; border-bottom: 1px solid var(--card-border);">
                        <h3 id="formTitle" style="font-size: 1.1rem; font-weight: 700; color: var(--text-main); margin: 0;">Update Record</h3>
                        <button type="button" onclick="document.getElementById('addEditRecordForm').style.display='none';" style="background: none; border: none; color: var(--text-muted); font-size: 1.4rem; cursor: pointer; line-height: 1;">&times;</button>
                    </div>
                    
                    <!-- Form Body -->
                    <form onsubmit="return false;" style="padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem;">
                        <input type="hidden" id="editRecordId">
                        
                        <!-- Name Field -->
                        <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                            <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Name (اسم)</label>
                            <input type="text" id="formName" class="calc-input" placeholder="e.g. احمد" style="height: 42px; font-size: 1.2rem; padding: 0.5rem 0.85rem; border-radius: 8px;">
                        </div>
                        
                        <!-- Total & Single side-by-side -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                                <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Total (مجموع)</label>
                                <input type="text" id="formTotal" class="calc-input" placeholder="0" style="height: 42px; font-size: 1.1rem; padding: 0.5rem 0.85rem; border-radius: 8px; direction: ltr; text-align: left;">
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                                <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Single (مفرد)</label>
                                <input type="text" id="formSingle" class="calc-input" placeholder="0" style="height: 42px; font-size: 1.1rem; padding: 0.5rem 0.85rem; border-radius: 8px; direction: ltr; text-align: left;">
                            </div>
                        </div>
                        
                        <!-- Origin Field -->
                        <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                            <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Origin (منشاء / اصل)</label>
                            <input type="text" id="formOrigin" class="calc-input" placeholder="e.g. Arabic / Urdu" style="height: 40px; font-size: 0.95rem; padding: 0.5rem 0.85rem; border-radius: 8px; direction: auto; text-align: right;">
                        </div>
                        
                        <!-- Meanings Field -->
                        <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                            <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Meanings (معنی)</label>
                            <input type="text" id="formMeanings" class="calc-input" placeholder="e.g. Highly Praised" style="height: 40px; font-size: 0.95rem; padding: 0.5rem 0.85rem; border-radius: 8px; direction: auto; text-align: right;">
                        </div>
                        
                        <!-- Buttons Footer -->
                        <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 0.5rem;">
                            <button type="button" id="btnCancelForm" class="btn" style="padding: 0.55rem 1.2rem; font-size: 0.85rem; border-radius: 8px;">Cancel</button>
                            <button type="button" id="btnSubmitForm" class="btn btn-primary" style="padding: 0.55rem 1.4rem; font-size: 0.85rem; border-radius: 8px;">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Saved Calculations Table -->
            <div class="history-table-wrapper">
                <table class="history-table">
                    <thead>
                        <!-- Row 1: Search Inputs, with numeric columns search-total & search-single styled LTR -->
                        <tr>
                            <th style="width: 26%;"><input type="text" class="table-search-input" id="search-name" placeholder="Search Name..."></th>
                            <th style="width: 10%;"><input type="text" class="table-search-input" id="search-total" placeholder="Total..." style="direction: ltr; text-align: left;"></th>
                            <th style="width: 8%; min-width: 65px;"><input type="text" class="table-search-input" id="search-single" placeholder="Single..." style="direction: ltr; text-align: left;"></th>
                            <th style="width: 14%; min-width: 80px;"><input type="text" class="table-search-input" id="search-origin" placeholder="Search Origin..."></th>
                            <th style="width: 22%;"><input type="text" class="table-search-input" id="search-meanings" placeholder="Search Meanings..."></th>
                            <th style="width: 20%; text-align: center;"><button id="btnClearFilters" class="btn" style="font-size: 0.75rem; padding: 0.3rem 0.6rem; border-color: rgba(255, 59, 48, 0.25); color: #FF453A; display: none;">Clear</button></th>
                        </tr>
                        <!-- Row 2: Sortable Column Headers -->
                        <tr id="sortHeaderRow">
                            <th class="sortable" data-col="name" style="width: 26%;">Name <span id="sort-icon-name" class="sort-icon">⇅</span></th>
                            <th class="sortable" data-col="total" style="width: 10%;">Total <span id="sort-icon-total" class="sort-icon">⇅</span></th>
                            <th class="sortable" data-col="single" style="width: 8%; min-width: 65px;">Single <span id="sort-icon-single" class="sort-icon">⇅</span></th>
                            <th class="sortable" data-col="origin" style="width: 14%; min-width: 80px;">Origin <span id="sort-icon-origin" class="sort-icon">⇅</span></th>
                            <th class="sortable" data-col="meanings" style="width: 22%;">Meanings <span id="sort-icon-meanings" class="sort-icon">⇅</span></th>
                            <th style="width: 20%; text-align: center;">Temperaments %</th>
                        </tr>
                    </thead>
                    <tbody id="historyTableBody">
                        <!-- Populated dynamically -->
                    </tbody>
                </table>
            </div>

            <!-- Table Pagination Controls -->
            <div id="tablePaginationContainer" style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; padding: 0.5rem 0.2rem; border-top: 1px solid var(--card-border); gap: 0.5rem; direction: ltr; font-size: 0.8rem; color: var(--text-muted);">
                <div style="display: flex; align-items: center; gap: 0.4rem;">
                    <span>Rows per page:</span>
                    <select id="pageSizeSelect" style="padding: 0.15rem 0.35rem; font-size: 0.8rem; background: transparent; border: 1px solid var(--card-border); border-radius: 4px; color: var(--text-main); outline: none;">
                        <option value="10" style="background: var(--card-bg);">10</option>
                        <option value="25" selected style="background: var(--card-bg);">25</option>
                        <option value="50" style="background: var(--card-bg);">50</option>
                        <option value="100" style="background: var(--card-bg);">100</option>
                    </select>
                    <span id="pageInfoText" style="margin-left: 0.4rem;">Showing 0-0 of 0</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.25rem;">
                    <button id="btnFirstPage" style="background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 0.2rem 0.4rem; font-size: 0.8rem; border-radius: 4px; transition: color 0.15s;">« First</button>
                    <button id="btnPrevPage" style="background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 0.2rem 0.4rem; font-size: 0.8rem; border-radius: 4px; transition: color 0.15s;">‹ Prev</button>
                    <span id="currentPageBadge" style="padding: 0.15rem 0.5rem; color: var(--accent-color); font-weight: 700; font-size: 0.8rem;">1</span>
                    <button id="btnNextPage" style="background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 0.2rem 0.4rem; font-size: 0.8rem; border-radius: 4px; transition: color 0.15s;">Next ›</button>
                    <button id="btnLastPage" style="background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 0.2rem 0.4rem; font-size: 0.8rem; border-radius: 4px; transition: color 0.15s;">Last »</button>
                </div>
            </div>

            <!-- Floating & Moveable Modal Keyboard Copy (Urdu alphabets only, numeric row on top) -->
            <div class="chart-section floating-keyboard" id="modalKeyboardContainer" style="display: none;">
                <div class="section-header" style="flex-direction: column; align-items: stretch; gap: 0.75rem;">
                    <!-- Top Numeric Row -->
                    <div id="modalKeyboardNumericRow" style="display: flex; gap: 0.4rem; justify-content: center; margin: 0 auto; direction: ltr; width: 100%; max-width: 640px;"></div>
                    
                    <!-- Action Row (Space bar & Backspace inside modal) -->
                    <div style="display: flex; gap: 0.4rem; width: 100%; max-width: 640px; margin: 0 auto;">
                        <button id="modalBtnSpaceBar" class="btn btn-primary" style="flex: 1; margin: 0; padding: 0.5rem 0; text-align: center; font-size: 0.8rem;">Space ␣</button>
                        <button id="modalBtnBackspace" class="btn" style="flex: 1; margin: 0; padding: 0.5rem 0; text-align: center; background: rgba(255, 59, 48, 0.1); border-color: rgba(255, 59, 48, 0.2); color: #FF453A; font-size: 0.8rem;">Delete ⌫</button>
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

        // Elemental Temperaments Mapping (Fire, Air, Water, Earth)
        const elementMap = {
            // Fire (آتشی)
            'ا': 'fire', 'آ': 'fire', 'ہ': 'fire', 'ه': 'fire', 'ھ': 'fire', 'ط': 'fire', 'م': 'fire', 'ف': 'fire', 'ش': 'fire', 'ذ': 'fire',
            // Air (بادی)
            'ب': 'air', 'پ': 'air', 'و': 'air', 'ی': 'air', 'ے': 'air', 'ن': 'air', 'ں': 'air', 'ص': 'air', 'ت': 'air', 'ٹ': 'air', 'ض': 'air',
            // Water (آبی)
            'ج': 'water', 'چ': 'water', 'ز': 'water', 'ژ': 'water', 'ک': 'water', 'گ': 'water', 'س': 'water', 'ق': 'water', 'ث': 'water', 'ظ': 'water',
            // Earth (خاکی)
            'د': 'earth', 'ڈ': 'earth', 'ح': 'earth', 'ل': 'earth', 'ع': 'earth', 'ر': 'earth', 'ڑ': 'earth', 'خ': 'earth', 'غ': 'earth'
        };

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

        // Pagination State
        let currentPage = 1;
        let pageSize = 25;

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

        // List of all editable text input fields
        const editableInputIds = [
            'calcInput', 'originInput', 'meaningInput',
            'formName', 'formTotal', 'formSingle', 'formOrigin', 'formMeanings',
            'search-name', 'search-total', 'search-single', 'search-origin', 'search-meanings'
        ];

        // Function to suppress or restore phone OS native virtual keyboard
        function setNativeKeyboardSuppression(suppress) {
            editableInputIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    if (suppress) {
                        el.setAttribute('inputmode', 'none');
                    } else {
                        el.removeAttribute('inputmode');
                    }
                }
            });
        }

        // Initially suppress native phone keyboard because custom built-in keyboard is active
        setNativeKeyboardSuppression(true);

        // Main Built-in Keyboard Toggle Handler
        const btnToggleMainKeyboard = document.getElementById('btnToggleMainKeyboard');
        const keyboardActionRow = document.getElementById('keyboardActionRow');
        const lettersGrid = document.getElementById('lettersGrid');
        let isMainKeyboardOpen = true;

        if (btnToggleMainKeyboard) {
            btnToggleMainKeyboard.addEventListener('click', () => {
                isMainKeyboardOpen = !isMainKeyboardOpen;
                if (isMainKeyboardOpen) {
                    keyboardActionRow.style.display = 'flex';
                    lettersGrid.style.display = 'flex';
                    btnToggleMainKeyboard.textContent = 'Hide Keyboard ⌨️';
                    btnToggleMainKeyboard.style.borderColor = 'var(--accent-color)';
                    setNativeKeyboardSuppression(true);
                } else {
                    keyboardActionRow.style.display = 'none';
                    lettersGrid.style.display = 'none';
                    btnToggleMainKeyboard.textContent = 'Show Keyboard ⌨️';
                    btnToggleMainKeyboard.style.borderColor = 'var(--card-border)';
                    setNativeKeyboardSuppression(false);
                }
            });
        }

        // Modal Open / Close Helpers
        function closeModal() {
            const memoModal = document.getElementById('memoModal');
            if (memoModal) {
                memoModal.style.display = 'none';
            }
        }

        function openModal() {
            const memoModal = document.getElementById('memoModal');
            if (memoModal) {
                loadHistory();
                memoModal.style.display = 'flex';
            }
        }

        // Back to Calculator button inside full-screen Saved/Memo view
        const btnBackToCalc = document.getElementById('btnBackToCalc');
        if (btnBackToCalc) {
            btnBackToCalc.addEventListener('click', () => {
                closeModal();
            });
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

            // Calculate Elemental Temperaments
            const elementCounts = { fire: 0, air: 0, water: 0, earth: 0 };
            let totalElementLetters = 0;

            for (let i = 0; i < inputVal.length; i++) {
                const char = inputVal[i];
                if (/\s/.test(char)) continue;

                if (letterMap[char] !== undefined || char === 'ء' || char === 'ئ' || char === 'ؤ' || char === 'ة') {
                    let element = null;
                    if (char === 'ء' || char === 'ئ' || char === 'ؤ' || char === 'ة') {
                        element = 'fire';
                    } else {
                        element = elementMap[char];
                    }

                    if (element) {
                        elementCounts[element]++;
                        totalElementLetters++;
                    }
                }
            }

            const elements = ['fire', 'air', 'water', 'earth'];
            elements.forEach(el => {
                const pct = totalElementLetters > 0 ? Math.round((elementCounts[el] / totalElementLetters) * 100) : 0;
                document.getElementById(`val-${el}`).textContent = `${pct}%`;
                document.getElementById(`bar-${el}`).style.width = `${pct}%`;
            });

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

        // MySQL Save functionality
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

        // MySQL Load history functionality (Populates modal table dynamically)
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

            // 3. Pagination calculation
            const totalRecords = processedData.length;
            const totalPages = Math.ceil(totalRecords / pageSize) || 1;
            if (currentPage > totalPages) currentPage = totalPages;
            if (currentPage < 1) currentPage = 1;

            const startIndex = (currentPage - 1) * pageSize;
            const endIndex = Math.min(startIndex + pageSize, totalRecords);
            const pageData = processedData.slice(startIndex, endIndex);

            // Update Pagination UI
            const pageInfo = document.getElementById('pageInfoText');
            if (pageInfo) {
                pageInfo.textContent = totalRecords === 0 
                    ? 'Showing 0 of 0' 
                    : `Showing ${startIndex + 1}-${endIndex} of ${totalRecords}`;
            }
            const currentBadge = document.getElementById('currentPageBadge');
            if (currentBadge) currentBadge.textContent = `${currentPage} / ${totalPages}`;

            const btnFirst = document.getElementById('btnFirstPage');
            const btnPrev = document.getElementById('btnPrevPage');
            const btnNext = document.getElementById('btnNextPage');
            const btnLast = document.getElementById('btnLastPage');

            if (btnFirst) btnFirst.disabled = currentPage === 1;
            if (btnPrev) btnPrev.disabled = currentPage === 1;
            if (btnNext) btnNext.disabled = currentPage === totalPages || totalPages === 0;
            if (btnLast) btnLast.disabled = currentPage === totalPages || totalPages === 0;

            if (totalRecords === 0) {
                tableBody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--text-muted); font-style: italic;">No matching records found</td></tr>';
                return;
            }

            tableBody.innerHTML = pageData.map(item => `
                <tr>
                    <td class="arabic-cell" style="position: relative;">
                        <a href="#" onclick="toggleRecordMenu(event, ${item.id}); return false;" style="color: var(--accent-color); font-weight: bold; text-decoration: none;">${item.name}</a>
                        <div id="record-menu-${item.id}" class="record-action-dropdown" style="display: none; position: absolute; right: 10px; top: 100%; z-index: 100; background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,0.3); padding: 0.4rem; min-width: 110px;">
                            <button class="btn btn-primary" onclick="startEditRecord(${item.id}); closeAllRecordMenus();" style="width: 100%; text-align: right; padding: 0.35rem 0.6rem; font-size: 0.75rem; border-radius: 4px; margin-bottom: 0.3rem;">✏️ Edit</button>
                            <button class="btn btn-danger" onclick="deleteRecord(${item.id}); closeAllRecordMenus();" style="width: 100%; text-align: right; padding: 0.35rem 0.6rem; font-size: 0.75rem; border-radius: 4px;">🗑️ Delete</button>
                        </div>
                    </td>
                    <td>${item.total}</td>
                    <td>${item.single}</td>
                    <td>${item.origin || '-'}</td>
                    <td>${item.meanings || '-'}</td>
                    <td style="text-align: center;">
                        ${(function() {
                            const elCounts = { fire: 0, air: 0, water: 0, earth: 0 };
                            let totalLetters = 0;
                            const str = item.name || '';
                            for (let i = 0; i < str.length; i++) {
                                const ch = str[i];
                                if (/\s/.test(ch)) continue;
                                let el = elementMap[ch];
                                if (!el && (ch === 'ء' || ch === 'ئ' || ch === 'ؤ' || ch === 'ة')) el = 'fire';
                                if (el) { elCounts[el]++; totalLetters++; }
                            }
                            const getPct = (el) => totalLetters > 0 ? Math.round((elCounts[el] / totalLetters) * 100) : 0;
                            return `
                                <div style="display: inline-flex; gap: 0.4rem; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 700; direction: ltr;">
                                    <span style="color: #ef4444;" title="Fire">${getPct('fire')}%</span>
                                    <span style="color: #f59e0b;" title="Air">${getPct('air')}%</span>
                                    <span style="color: #38bdf8;" title="Water">${getPct('water')}%</span>
                                    <span style="color: #10b981;" title="Earth">${getPct('earth')}%</span>
                                </div>
                            `;
                        })()}
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

        // Toggle action dropdown menu when clicking record name
        function toggleRecordMenu(e, id) {
            e.stopPropagation();
            const targetMenu = document.getElementById(`record-menu-${id}`);
            const isCurrentlyOpen = targetMenu && targetMenu.style.display === 'block';
            closeAllRecordMenus();
            if (targetMenu && !isCurrentlyOpen) {
                targetMenu.style.display = 'block';
            }
        }

        function closeAllRecordMenus() {
            document.querySelectorAll('.record-action-dropdown').forEach(el => {
                el.style.display = 'none';
            });
        }

        document.addEventListener('click', () => {
            closeAllRecordMenus();
        });

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
            addEditRecordForm.style.display = 'flex';
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
            addEditRecordForm.style.display = 'flex';
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

        // Submit Form (handles both Add and Edit via MySQL endpoints)
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

        // Pagination Controls Listeners
        const pageSizeSel = document.getElementById('pageSizeSelect');
        if (pageSizeSel) {
            pageSizeSel.addEventListener('change', (e) => {
                pageSize = parseInt(e.target.value, 10) || 25;
                currentPage = 1;
                renderTable();
            });
        }
        document.getElementById('btnFirstPage')?.addEventListener('click', () => {
            currentPage = 1;
            renderTable();
        });
        document.getElementById('btnPrevPage')?.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                renderTable();
            }
        });
        document.getElementById('btnNextPage')?.addEventListener('click', () => {
            currentPage++;
            renderTable();
        });
        document.getElementById('btnLastPage')?.addEventListener('click', () => {
            const totalRecords = calculationsHistory.length;
            currentPage = Math.ceil(totalRecords / pageSize) || 1;
            renderTable();
        });

        // Search input keyup/change handlers
        const searchInputs = ['name', 'total', 'single', 'origin', 'meanings'];
        searchInputs.forEach(col => {
            const input = document.getElementById(`search-${col}`);
            input.addEventListener('input', (e) => {
                currentFilters[col] = e.target.value.trim();
                currentPage = 1;
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
            currentPage = 1;
            renderTable();
        });

        // Modal Open / Close Event Listeners
        const memoModal = document.getElementById('memoModal');
        const btnMemo = document.getElementById('btnMemo');
        if (btnMemo) {
            btnMemo.addEventListener('click', openModal);
        }

        const btnCloseModal = document.getElementById('btnCloseModal');
        if (btnCloseModal) {
            btnCloseModal.addEventListener('click', closeModal);
        }

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
