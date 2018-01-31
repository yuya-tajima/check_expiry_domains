# これは更新期限が近づいているドメインをチェックします

管理しているドメインの更新期限が近づくと各サービスがメールでお知らせしてくれますが、たくさん契約していると確認するのが大変です。
このプログラムは、管理しているドメインをスクレイピングによって取得し、出力します。

ドメイン管理サービス側で特にAPIは提供されていなさそうだったので、スクレイピングで取得する実装にしています。
IDとパスワードを間違えて失敗の回数を重ねると、そのアクセス元のIPアドレスが拒否されるケースがありそうなので、注意して下さい。

その他不具合、問題、報告があれば、PRなどでご連絡下さい。

## 対象のドメイン管理サービス

以下のドメイン管理サービスを対象にします。

- VALUE-DOMAIN(バリュードメイン) 
- ゴンベエドメイン
- お名前.com
- さくらインターネット

## Usage

管理画面にログインしてスクレイピングするので、各ドメインサービスのコンパネにログインする為のユーザー名とパスワードを、呼び出すプロセス側であらかじめ環境変数として定義しておきます。

下記はVALUE-DOMAINの例です。その他のサービスの環境変数名についてはソースコードのコメントを参照して下さい。
環境変数が定義されていない場合、そのサービスにはアクセスしません。

下記の出力例のドメインは仮です。実際はご契約されているドメイン名が出力されます。

WARNINGから下のxxxxxx.jpの更新日が30日以内に迫っていると報告します。
INFO以下(yyyyyy.jpやzzzzzz.jpなど)は更新日まで30日以上あります。

```
$ export VALUE_DOMAIN_USER=hoge
$ export VALUE_DOMAIN_PASS=foo
$ php check_expiry_domains.php

# 出力例
WARNING: The expiry date is approaching.

Domain service is value-domain
xxxxxx.jp will expire in 28 days. expiry date is 2018-02-28.

INFO: There are over 30 days until the expiry date.

Domain service is value-domain
yyyyyy.jp 2018-11-30
zzzzzz.jp 2018-08-31
... 省略
```
