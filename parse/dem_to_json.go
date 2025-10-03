package main

import (
    "encoding/json"
    "fmt"
    "log"
    "os"

    "github.com/dotabuff/manta"
    "github.com/dotabuff/manta/dota"
)

func main() {
    if len(os.Args) < 2 {
        log.Fatal("请提供 .dem 文件路径")
    }

    demFile := os.Args[1]
    f, err := os.Open(demFile)
    if err != nil {
        log.Fatal(err)
    }
    defer f.Close()

    parser, err := manta.NewStreamParser(f)
    if err != nil {
        log.Fatal(err)
    }

    // 这里保存最终要输出的 JSON 数据
    output := map[string]interface{}{}

    parser.Callbacks.OnCDemoFileInfo(func(info *dota.CDemoFileInfo) error {
        output["match_id"] = info.GameInfo.Dota.MatchId
        output["game_winner"] = info.GameInfo.Dota.GameWinner
        output["players"] = info.GameInfo.Dota.PlayerInfo
        output["picks_bans"] = info.GameInfo.Dota.PicksBans
        output["end_time"] = info.GameInfo.Dota.EndTime
        return nil
    })

    if err := parser.Start(); err != nil {
        log.Fatal(err)
    }

    // 把 JSON 原封不动输出到 stdout
    b, _ := json.Marshal(output)
    fmt.Println(string(b))
}
