function factorial(n) {
    let product = 1;
    for (let i = n; i > 1; i--) {
        product *= i;
    }
    return product;
}

const readline = require("readline").createInterface({
    input: process.stdin,
    output: process.stdout
});

readline.question("Enter a number: ", (answer) => {
    const num = parseInt(answer, 10);

    if (num < 0) {
        console.log("Factorial of negative number can't be calculated!");
    } else {
        console.log("Factorial of " + num + " = " + factorial(num));
    }

    readline.close();
});
