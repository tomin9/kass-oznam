# Kass Oznam

WordPress plugin: medzistránka s upozornením (napr. „podujatie bolo presunuté do Kina Baník“) pred presmerovaním na externý predaj vstupeniek.

## Inštalácia
1. Súbor `kass-oznam.php` zabaľte do ZIP (`kass-oznam.zip`, v ňom priečinok `kass-oznam/`) a nahrajte cez *Pluginy → Pridať nový → Nahrať plugin*, alebo plugin nasaďte cez WP Pusher z vetvy `main`.
2. Aktivujte plugin.
3. Vytvorte stránku (napr. „Vstupenky“, adresa `/vstupenky/`) a vložte do nej `[kass_vstupenky]`.
4. *Nastavenia → Kass Oznam*: upravte text upozornenia, zadajte adresu stránky a pridajte podujatia (`kod | Názov | odkaz na predaj`).
5. Na stránkach podujatí nahraďte pôvodný odkaz na predaj odkazom zobrazeným v nastaveniach, napr. `https://vasastranka.sk/vstupenky/?podujatie=koncert-jar`.

Text upozornenia sa upravuje na jednom mieste pre všetky podujatia.
