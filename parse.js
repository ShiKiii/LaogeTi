const fs = require('fs');
const { DemoFile } = require('demofile');

if (process.argv.length < 3) {
    console.error("Usage: node parse.js <file.dem>");
    process.exit(1);
}

const filepath = process.argv[2];
const buffer = fs.readFileSync(filepath);
const demoFile = new DemoFile();

demoFile.on("end", e => {
    let data = {
        matchEnded: true,
        players: demoFile.players.map(p => ({
            name: p.name,
            steamId: p.steamId,
            kills: p.kills,
            deaths: p.deaths,
            assists: p.assists,
            gold: p.gold
        }))
    };
    console.log(JSON.stringify(data, null, 2));
});

demoFile.parse(buffer);
