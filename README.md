# Kass Oznam

WordPress plugin: medzistránka s upozornením (napr. „podujatie bolo presunuté do Kina Baník“) pred presmerovaním na externý predaj vstupeniek.

## Inštalácia
1. Priečinok `kass-oznam` zabaľte do ZIP (`kass-oznam.zip`) a nahrajte cez *Pluginy → Pridať nový → Nahrať plugin*, alebo ho skopírujte do `wp-content/plugins/`.
2. Aktivujte plugin.
3. Vytvorte stránku (napr. „Vstupenky“, adresa `/vstupenky/`) a vložte do nej `[kass_vstupenky]`.
4. *Nastavenia → Kass Oznam*: upravte text upozornenia, zadajte adresu stránky a pridajte podujatia (`kod | Názov | odkaz na predaj`).
5. Na stránkach podujatí nahraďte pôvodný odkaz na predaj odkazom zobrazeným v nastaveniach, napr. `https://vasastranka.sk/vstupenky/?podujatie=koncert-jar`.

Text upozornenia sa upravuje na jednom mieste pre všetky podujatia.
