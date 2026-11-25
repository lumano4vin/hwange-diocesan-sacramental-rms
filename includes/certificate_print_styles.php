<?php
/**
 * Shared Print & PDF Margin Stylesheet for All Sacramental Certificates
 * Guarantees full single-page visibility inside safe printable margins on
 * both A4 (210x297mm) and US Letter (215.9x279.4mm) across all themes.
 */
?>
<style id="certificate-safe-print-styles">
    @page {
        size: A4 portrait;
        margin: 10mm;
    }

    /* Clean 1:1 A4 (minus 8mm margin = 194x281mm -> 733x1036px at 96dpi) PDF capture box */
    .certificate-container.pdf-generation {
        position: relative !important;
        left: 0 !important;
        top: 0 !important;
        margin: 0 !important;
        padding: 16px 28px !important;
        box-shadow: none !important;
        transform: none !important;
        width: 733px !important;
        min-width: 733px !important;
        max-width: 733px !important;
        height: 1036px !important;
        min-height: 1036px !important;
        max-height: 1036px !important;
        box-sizing: border-box !important;
        overflow: hidden !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: space-between !important;
    }

    .certificate-container.pdf-generation .field-value {
        min-width: 180px !important;
        max-width: 100% !important;
        font-size: 18px !important;
    }

    .certificate-container.pdf-generation .details-grid,
    .certificate-container.pdf-generation .couple-display {
        width: 100% !important;
        max-width: 100% !important;
    }

    @media print {
        *, *:before, *:after {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            box-sizing: border-box !important;
        }

        .no-print, .theme-selector {
            display: none !important;
        }

        html, body {
            background: #ffffff !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            height: auto !important;
            min-height: 0 !important;
            max-height: none !important;
            overflow: visible !important;
        }

        .certificate-container,
        body.theme-gold-leaf .certificate-container,
        body.theme-latin .certificate-container,
        body.theme-minimal .certificate-container,
        body.theme-heirloom .certificate-container {
            position: relative !important;
            left: 0 !important;
            top: 0 !important;
            transform: none !important;
            margin: 0 auto !important;
            box-shadow: none !important;
            width: 100% !important;
            max-width: 182mm !important;
            min-width: 0 !important;
            height: auto !important;
            min-height: 244mm !important;
            max-height: 250mm !important;
            box-sizing: border-box !important;
            padding: 5mm 8mm 4mm 8mm !important;
            overflow: hidden !important;
            page-break-inside: avoid !important;
            page-break-before: avoid !important;
            page-break-after: avoid !important;
            break-inside: avoid !important;
            break-before: avoid !important;
            break-after: avoid !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
        }

        body.theme-latin .certificate-container {
            border-width: 12px !important;
        }

        .certificate-container .header {
            margin-top: 0 !important;
            margin-bottom: 4px !important;
        }

        .certificate-container .diocese-name {
            font-size: 19px !important;
            letter-spacing: 1.5px !important;
        }

        .certificate-container .diocese-sub {
            font-size: 9.5px !important;
            letter-spacing: 1.8px !important;
            margin-top: 1px !important;
        }

        .certificate-container .certificate-title {
            font-size: 30px !important;
            margin: 2px 0 !important;
            line-height: 1.1 !important;
        }

        body.theme-heirloom .certificate-container .certificate-title {
            font-size: 24px !important;
            margin: 2px 0 !important;
        }

        .certificate-container .content {
            font-size: 12.5px !important;
            line-height: 1.25 !important;
            width: 100% !important;
            max-width: 100% !important;
            flex: 1 1 auto !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
            align-items: center !important;
            margin: 0 !important;
        }

        .certificate-container .content p {
            margin: 3px 0 !important;
        }

        .certificate-container .field-value {
            font-size: 16px !important;
            padding-bottom: 2px !important;
            min-width: 160px !important;
            max-width: 100% !important;
            word-break: break-word !important;
        }

        .certificate-container .field-label {
            font-size: 9px !important;
            margin-top: 3px !important;
            letter-spacing: 1.2px !important;
        }

        .certificate-container .sacrament-declaration {
            font-size: 18px !important;
            margin: 3px 0 !important;
            letter-spacing: 1.2px !important;
        }

        body.theme-heirloom .certificate-container .sacrament-declaration {
            font-size: 16px !important;
        }

        .certificate-container .details-grid {
            width: 100% !important;
            max-width: 100% !important;
            gap: 5px 16px !important;
            margin: 6px auto 0 auto !important;
        }

        .certificate-container .details-grid div div {
            font-size: 12.5px !important;
            padding-bottom: 2px !important;
        }

        .certificate-container .couple-display {
            width: 100% !important;
            max-width: 100% !important;
            margin: 6px auto !important;
            padding: 8px 12px !important;
        }

        .certificate-container .marginal-notations-box {
            width: 100% !important;
            margin-top: 4px !important;
            padding: 4px 8px !important;
            font-size: 9px !important;
        }

        .certificate-container .footer {
            width: 100% !important;
            margin-top: auto !important;
            padding-top: 4px !important;
            display: flex !important;
            justify-content: space-between !important;
            align-items: flex-end !important;
        }

        .certificate-container .seal-container > div:first-child {
            width: 72px !important;
            height: 72px !important;
            margin-bottom: 4px !important;
            box-shadow: none !important;
        }

        .certificate-container .seal {
            width: 68px !important;
            height: 68px !important;
            font-size: 7px !important;
            padding: 4px !important;
        }

        .certificate-container .signature-block {
            width: 200px !important;
        }

        .certificate-container .signature-block #qrcode canvas,
        .certificate-container .signature-block #qrcode img {
            width: 64px !important;
            height: 64px !important;
        }

        .certificate-container .signature-line {
            margin-top: 8px !important;
            margin-bottom: 3px !important;
        }

        .certificate-container .signature-label {
            font-size: 10px !important;
        }

        .certificate-container .canonical-meta {
            font-size: 7.5px !important;
            line-height: 1.2 !important;
            margin-top: 4px !important;
            padding-top: 4px !important;
        }
    }
</style>
