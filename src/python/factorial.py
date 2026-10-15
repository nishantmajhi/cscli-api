def factorial(n):
    product = 1
    for i in range(n, 1, -1):
        product *= i
    return product


def main():
    num = int(input("Enter a number: "))

    if num < 0:
        print("Factorial of negative number can't be calculated!")
        return

    print(f"Factorial of {num} = {factorial(num)}")


if __name__ == "__main__":
    main()
