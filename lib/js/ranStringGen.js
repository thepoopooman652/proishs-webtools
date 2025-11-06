function genString(charList, length, amount) {
    length = Number(length);
    amount = Number(amount);

    for (let j = 0; j < amount; j++) {
        let result = "";
        for (let i = 0; i < length; i++) {
            const randomIndex = Math.floor(Math.random() * charList.length);
            result += charList[randomIndex];
        }
        return result;
    }
}