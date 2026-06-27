@props([
    'href',
    'label',
    'align' => 'center',
])

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0;">
    <tr>
        <td align="{{ $align }}" style="padding:0;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 auto;">
                <tr>
                    <td align="center" bgcolor="#235347" style="border-radius:6px;background-color:#235347;">
                        <a href="{{ $href }}"
                           style="display:inline-block;padding:12px 28px;font-family:'Segoe UI',Arial,Helvetica,sans-serif;font-size:14px;font-weight:600;line-height:1.4;color:#ffffff;text-decoration:none;border-radius:6px;background-color:#235347;">
                            {{ $label }}
                        </a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
