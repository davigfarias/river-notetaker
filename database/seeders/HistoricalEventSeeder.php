<?php

namespace Database\Seeders;

use App\Enums\Era;
use App\Enums\HistoricalEventNature;
use App\Models\AccessToken;
use App\Models\HistoricalEvent;
use Illuminate\Database\Seeder;

/**
 * Additive demo data for the Histórico timeline. Idempotent (firstOrCreate) and
 * never deletes anything. Run with: php artisan db:seed --class=HistoricalEventSeeder
 */
class HistoricalEventSeeder extends Seeder
{
    public function run(): void
    {
        $token = AccessToken::query()->orderBy('id')->first();

        if (! $token) {
            $this->command->warn('Nenhum AccessToken encontrado: entre no app uma vez antes de rodar este seeder.');

            return;
        }

        foreach ($this->events() as [$title, $nature, $startYear, $startEra, $endYear, $endEra, $description]) {
            HistoricalEvent::query()->firstOrCreate(
                [
                    'access_token_id' => $token->id,
                    'title' => $title,
                    'start_year' => $startYear,
                    'start_era' => $startEra,
                ],
                [
                    'nature' => $nature,
                    'end_year' => $endYear,
                    'end_era' => $endYear === null ? null : $endEra,
                    'description' => $description,
                ],
            );
        }
    }

    /**
     * @return array<int, array{0: string, 1: HistoricalEventNature, 2: int, 3: Era, 4: int|null, 5: Era|null, 6: string|null}>
     */
    private function events(): array
    {
        $bc = Era::BeforeChrist;
        $ad = Era::AnnoDomini;
        $event = HistoricalEventNature::Event;
        $book = HistoricalEventNature::Book;
        $person = HistoricalEventNature::Person;
        $document = HistoricalEventNature::Document;
        $artwork = HistoricalEventNature::Artwork;
        $article = HistoricalEventNature::Article;

        return [
            ['Construção da Grande Pirâmide de Gizé', $artwork, 2560, $bc, null, null, 'Túmulo do faraó Quéops, a única das Sete Maravilhas do mundo antigo ainda de pé.'],
            ['Código de Hamurábi', $document, 1754, $bc, null, null, 'Um dos mais antigos códigos de leis escritos, gravado em estela de basalto na Babilônia.'],
            ['Chamado de Abraão (data tradicional)', $person, 2000, $bc, null, null, 'Saída de Ur dos caldeus rumo a Canaã, segundo a cronologia tradicional.'],
            ['Êxodo do Egito (data tradicional)', $event, 1446, $bc, null, null, 'Datação conservadora baseada em 1 Reis 6.1.'],
            ['Reinado de Davi', $person, 1010, $bc, 970, $bc, 'Unificação de Israel e estabelecimento de Jerusalém como capital.'],
            ['Dedicação do Primeiro Templo', $event, 957, $bc, null, null, 'Templo construído por Salomão em Jerusalém.'],
            ['Ilíada e Odisseia (composição)', $book, 750, $bc, null, null, 'Épicos atribuídos a Homero, fundacionais para a literatura grega.'],
            ['Queda de Samaria', $event, 722, $bc, null, null, 'Assíria conquista o Reino do Norte (Israel).'],
            ['Destruição de Jerusalém e exílio babilônico', $event, 586, $bc, null, null, 'Nabucodonosor destrói o Templo; início do exílio.'],
            ['Decreto de Ciro', $document, 538, $bc, null, null, 'Permite o retorno dos judeus a Judá.'],
            ['Nascimento de Confúcio', $person, 551, $bc, null, null, null],
            ['Batalha de Maratona', $event, 490, $bc, null, null, 'Atenienses derrotam os persas.'],
            ['Guerra do Peloponeso', $event, 431, $bc, 404, $bc, 'Atenas contra Esparta; narrada por Tucídides.'],
            ['Morte de Sócrates', $person, 399, $bc, null, null, 'Condenado a beber cicuta em Atenas.'],
            ['A República', $book, 375, $bc, null, null, 'Diálogo de Platão sobre justiça e a cidade ideal.'],
            ['Ética a Nicômaco', $book, 340, $bc, null, null, 'Obra de Aristóteles sobre a virtude e a felicidade.'],
            ['Conquistas de Alexandre, o Grande', $person, 336, $bc, 323, $bc, 'Do Egito à Índia em pouco mais de uma década.'],
            ['Septuaginta (início da tradução)', $book, 250, $bc, null, null, 'Tradução grega das Escrituras hebraicas em Alexandria.'],
            ['Revolta dos Macabeus', $event, 167, $bc, 160, $bc, 'Rededicação do Templo, lembrada no Hanukkah.'],
            ['Assassinato de Júlio César', $event, 44, $bc, null, null, 'Morto nos Idos de Março, no Senado romano.'],
            ['Principado de Augusto', $person, 27, $bc, 14, $ad, 'Primeiro imperador de Roma; início da Pax Romana.'],
            ['Nascimento de Jesus (data provável)', $person, 5, $bc, null, null, 'Antes da morte de Herodes, o Grande (4 a.C.).'],
            ['Crucificação de Jesus', $event, 30, $ad, null, null, 'Sob Pôncio Pilatos, em Jerusalém.'],
            ['Concílio de Jerusalém', $event, 49, $ad, null, null, 'Registrado em Atos 15.'],
            ['Epístola aos Romanos', $document, 57, $ad, null, null, 'Escrita por Paulo em Corinto.'],
            ['Destruição do Segundo Templo', $event, 70, $ad, null, null, 'Tito toma Jerusalém.'],
            ['Erupção do Vesúvio', $event, 79, $ad, null, null, 'Pompeia e Herculano soterradas.'],
            ['Contra as Heresias', $book, 180, $ad, null, null, 'Irineu de Lyon contra o gnosticismo.'],
            ['Édito de Milão', $document, 313, $ad, null, null, 'Tolerância religiosa no Império Romano.'],
            ['Concílio de Niceia', $event, 325, $ad, null, null, 'Primeiro concílio ecumênico; contra o arianismo.'],
            ['Sobre a Encarnação do Verbo', $book, 318, $ad, null, null, 'Tratado de Atanásio de Alexandria.'],
            ['Concílio de Constantinopla', $event, 381, $ad, null, null, 'Amplia o Credo Niceno.'],
            ['Confissões', $book, 397, $ad, 400, $ad, 'Autobiografia espiritual de Agostinho de Hipona.'],
            ['Saque de Roma pelos visigodos', $event, 410, $ad, null, null, 'Ocasião para Agostinho escrever A Cidade de Deus.'],
            ['A Cidade de Deus', $book, 413, $ad, 426, $ad, 'Agostinho sobre as duas cidades.'],
            ['Concílio de Calcedônia', $event, 451, $ad, null, null, 'Definição das duas naturezas de Cristo.'],
            ['Queda do Império Romano do Ocidente', $event, 476, $ad, null, null, 'Deposição de Rômulo Augústulo.'],
            ['A Consolação da Filosofia', $book, 524, $ad, null, null, 'Boécio, escrito na prisão.'],
            ['Hégira', $event, 622, $ad, null, null, 'Migração de Maomé de Meca a Medina.'],
            ['Coroação de Carlos Magno', $event, 800, $ad, null, null, 'Coroado imperador pelo papa Leão III no Natal.'],
            ['Grande Cisma do Oriente', $event, 1054, $ad, null, null, 'Ruptura entre Roma e Constantinopla.'],
            ['Batalha de Hastings', $event, 1066, $ad, null, null, 'Conquista normanda da Inglaterra.'],
            ['Cur Deus Homo', $book, 1098, $ad, null, null, 'Anselmo de Cantuária sobre a expiação.'],
            ['Magna Carta', $document, 1215, $ad, null, null, 'Limita o poder do rei João da Inglaterra.'],
            ['Suma Teológica', $book, 1265, $ad, 1274, $ad, 'Tomás de Aquino; inacabada.'],
            ['A Divina Comédia', $book, 1308, $ad, 1320, $ad, 'Dante Alighieri.'],
            ['Guerra dos Cem Anos', $event, 1337, $ad, 1453, $ad, 'Inglaterra contra França.'],
            ['Peste Negra na Europa', $event, 1347, $ad, 1351, $ad, 'Matou talvez um terço da população europeia.'],
            ['Queda de Constantinopla', $event, 1453, $ad, null, null, 'Tomada pelos otomanos sob Maomé II.'],
            ['Bíblia de Gutenberg', $book, 1455, $ad, null, null, 'Primeiro grande livro impresso com tipos móveis na Europa.'],
            ['Chegada de Colombo à América', $event, 1492, $ad, null, null, null],
            ['Chegada de Cabral ao Brasil', $event, 1500, $ad, null, null, null],
            ['Teto da Capela Sistina', $artwork, 1508, $ad, 1512, $ad, 'Pintado por Michelangelo.'],
            ['95 Teses', $document, 1517, $ad, null, null, 'Lutero em Wittenberg; marco da Reforma.'],
            ['Dieta de Worms', $event, 1521, $ad, null, null, 'Lutero se recusa a retratar-se.'],
            ['Confissão de Augsburgo', $document, 1530, $ad, null, null, 'Redigida por Melanchthon.'],
            ['Institutas da Religião Cristã', $book, 1536, $ad, null, null, 'Primeira edição, de João Calvino.'],
            ['Concílio de Trento', $event, 1545, $ad, 1563, $ad, 'Resposta católica à Reforma.'],
            ['Catecismo de Heidelberg', $document, 1563, $ad, null, null, null],
            ['Bíblia King James', $book, 1611, $ad, null, null, null],
            ['Sínodo de Dort', $event, 1618, $ad, 1619, $ad, 'Cânones contra o arminianismo.'],
            ['Assembleia de Westminster', $event, 1643, $ad, 1653, $ad, 'Produziu a Confissão e os Catecismos de Westminster.'],
            ['Confissão de Fé de Westminster', $document, 1646, $ad, null, null, null],
            ['O Peregrino', $book, 1678, $ad, null, null, 'John Bunyan.'],
            ['Principia Mathematica', $book, 1687, $ad, null, null, 'Isaac Newton.'],
            ['Jonathan Edwards', $person, 1703, $ad, 1758, $ad, 'Teólogo do Grande Despertamento.'],
            ['Declaração de Independência dos EUA', $document, 1776, $ad, null, null, null],
            ['Revolução Francesa', $event, 1789, $ad, 1799, $ad, null],
            ['Independência do Brasil', $event, 1822, $ad, null, null, null],
            ['A Origem das Espécies', $book, 1859, $ad, null, null, 'Charles Darwin.'],
            ['Abolição da escravatura no Brasil', $document, 1888, $ad, null, null, 'Lei Áurea.'],
            ['Charles Spurgeon', $person, 1834, $ad, 1892, $ad, 'O "príncipe dos pregadores".'],
            ['Primeira Guerra Mundial', $event, 1914, $ad, 1918, $ad, null],
            ['Cristianismo e Liberalismo', $book, 1923, $ad, null, null, 'J. Gresham Machen.'],
            ['Segunda Guerra Mundial', $event, 1939, $ad, 1945, $ad, null],
            ['Descoberta dos Manuscritos do Mar Morto', $event, 1947, $ad, null, null, 'Cavernas de Qumran.'],
            ['Cristianismo Puro e Simples', $book, 1952, $ad, null, null, 'C. S. Lewis, a partir de palestras de rádio.'],
            ['Pacto de Lausanne', $document, 1974, $ad, null, null, 'Congresso Internacional de Evangelização Mundial.'],
            ['Queda do Muro de Berlim', $event, 1989, $ad, null, null, null],
            ['Attention Is All You Need', $article, 2017, $ad, null, null, 'Artigo que introduziu a arquitetura Transformer.'],
        ];
    }
}
